<?php

namespace App\Http\Controllers;

use App\Models\Home;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImpersonationController extends Controller
{
    public function store(Request $request, Home $home, User $user, AuditLogger $auditLogger): RedirectResponse
    {
        abort_if($request->session()->has('impersonator_id'), 403);
        abort_if((int) $request->user()->id === (int) $user->id, 403);
        abort_unless((int) $user->home_id === (int) $home->id, 404);
        abort_unless($home->status === 'active' && $user->is_active, 403);

        $impersonator = $request->user();

        $auditLogger->log('impersonation.started', [
            'actor_id' => $impersonator->id,
            'auditable' => $user,
            'event' => 'Impersonation',
            'friendly_action' => 'Started impersonating',
            'friendly_subject' => $user->name,
            'friendly_actor' => $impersonator->name,
            'friendly_summary' => "{$impersonator->name} started using {$user->name}'s account.",
            'metadata' => [
                'Administrator' => $impersonator->name,
                'Impersonated user' => $user->name,
                'Home' => $home->name,
            ],
        ]);

        $request->session()->put([
            'impersonator_id' => $impersonator->id,
            'impersonator_name' => $impersonator->name,
            'impersonated_user_name' => $user->name,
            'impersonated_home_id' => $home->id,
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('status', 'You are now impersonating '.$user->name.'.');
    }

    public function destroy(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $impersonatorId = $request->session()->pull('impersonator_id');
        abort_unless($impersonatorId, 403);

        $homeId = $request->session()->pull('impersonated_home_id');
        $impersonatedUserName = $request->session()->get('impersonated_user_name');
        $request->session()->forget(['impersonator_name', 'impersonated_user_name']);

        $impersonator = User::findOrFail($impersonatorId);

        $auditLogger->log('impersonation.ended', [
            'actor_id' => $impersonator->id,
            'auditable' => $impersonator,
            'event' => 'Impersonation',
            'friendly_action' => 'Stopped impersonating',
            'friendly_subject' => $impersonatedUserName ?: 'another user',
            'friendly_actor' => $impersonator->name,
            'friendly_summary' => "{$impersonator->name} stopped using ".($impersonatedUserName ?: 'another user')."'s account.",
            'metadata' => [
                'Administrator' => $impersonator->name,
                'Impersonated user' => $impersonatedUserName,
                'Home ID' => $homeId,
            ],
        ]);

        Auth::login($impersonator);
        $request->session()->regenerate();

        if ($homeId && Home::whereKey($homeId)->exists() && $impersonator->hasPermission('home_users.manage')) {
            return redirect()->route('homes.users.index', $homeId)->with('status', 'Returned to administrator account.');
        }

        return redirect()->route('dashboard')->with('status', 'Returned to administrator account.');
    }
}
