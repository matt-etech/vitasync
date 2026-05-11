<?php

namespace App\Services;

use App\Models\LoginHistory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class CarerAuthenticationService
{
    /**
     * @param array{email: string, password: string, device_timezone?: string|null, device_datetime?: string|null} $credentials
     */
    public function authenticate(array $credentials, Request $request): User
    {
        $user = User::query()
            ->with(['home', 'roles', 'carerProfile'])
            ->where('email', $credentials['email'])
            ->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials do not match our carer records.'],
            ]);
        }

        if (! $user->is_active) {
            abort(403, 'This carer account is not active.');
        }

        if (! $user->roles->contains(fn ($role): bool => $role->name === 'Carer' && $role->is_active)) {
            abort(403, 'This login is only available to carers.');
        }

        LoginHistory::create([
            'user_id' => $user->id,
            'logged_in_at' => now(),
            'login_timezone' => $credentials['device_timezone'] ?? null,
            'logged_in_device_at' => $credentials['device_datetime'] ?? null,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return $user;
    }
}
