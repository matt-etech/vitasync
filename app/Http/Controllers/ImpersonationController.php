<?php

namespace App\Http\Controllers;

use App\Models\Home;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImpersonationController extends Controller
{
    public function store(Request $request, Home $home, User $user): RedirectResponse
    {
        abort_if($request->session()->has('impersonator_id'), 403);
        abort_if((int) $request->user()->id === (int) $user->id, 403);
        abort_unless((int) $user->home_id === (int) $home->id, 404);
        abort_unless($home->status === 'active' && $user->is_active, 403);

        $impersonator = $request->user();

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

    public function destroy(Request $request): RedirectResponse
    {
        $impersonatorId = $request->session()->pull('impersonator_id');
        abort_unless($impersonatorId, 403);

        $homeId = $request->session()->pull('impersonated_home_id');
        $request->session()->forget(['impersonator_name', 'impersonated_user_name']);

        $impersonator = User::findOrFail($impersonatorId);

        Auth::login($impersonator);
        $request->session()->regenerate();

        if ($homeId && Home::whereKey($homeId)->exists() && $impersonator->hasPermission('home_users.manage')) {
            return redirect()->route('homes.users.index', $homeId)->with('status', 'Returned to administrator account.');
        }

        return redirect()->route('dashboard')->with('status', 'Returned to administrator account.');
    }
}
