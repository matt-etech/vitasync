<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\AuthenticateUserRequest;
use App\Models\LoginHistory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Arr;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function store(AuthenticateUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $credentials = Arr::only($validated, ['email', 'password']);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => 'The provided credentials do not match our records.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        $loginHistory = LoginHistory::create([
            'user_id' => Auth::id(),
            'logged_in_at' => now(),
            'login_timezone' => $validated['device_timezone'] ?? null,
            'logged_in_device_at' => $validated['device_datetime'] ?? null,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
        $request->session()->put('login_history_id', $loginHistory->id);

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $userId = Auth::id();

        if ($userId) {
            $loginHistory = LoginHistory::query()
                ->where('user_id', $userId)
                ->whereNull('logged_out_at')
                ->when(
                    $request->session()->has('login_history_id'),
                    fn ($query) => $query->whereKey($request->session()->get('login_history_id')),
                    fn ($query) => $query->latest('logged_in_at')
                )
                ->first();

            if ($loginHistory) {
                $attributes = ['logged_out_at' => now()];

                if ($request->filled('device_timezone')) {
                    $attributes['logout_timezone'] = substr((string) $request->input('device_timezone'), 0, 100);
                }

                if ($request->filled('device_datetime')) {
                    $attributes['logged_out_device_at'] = substr((string) $request->input('device_datetime'), 0, 100);
                }

                $loginHistory->update($attributes);
            }
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
