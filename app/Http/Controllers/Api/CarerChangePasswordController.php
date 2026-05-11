<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CarerChangePasswordRequest;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class CarerChangePasswordController extends Controller
{
    public function __invoke(CarerChangePasswordRequest $request, AuditLogger $auditLogger): JsonResponse
    {
        $carer = User::query()->with('roles')->findOrFail($request->integer('carer_id'));

        if (! $carer->is_active || ! $carer->roles->contains(fn ($role): bool => $role->name === 'Carer' && $role->is_active)) {
            abort(403, 'This endpoint is only available to active carers.');
        }

        if (! Hash::check($request->string('current_password')->toString(), $carer->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password is not correct.'],
            ]);
        }

        $carer->forceFill([
            'password' => Hash::make($request->string('password')->toString()),
        ])->save();

        $auditLogger->log('carer.password_changed', [
            'actor_id' => $carer->id,
            'auditable' => $carer,
            'event' => 'User',
            'metadata' => [
                'changed_by' => 'carer_mobile_profile',
            ],
        ]);

        return response()->json([
            'message' => 'Password changed.',
        ]);
    }
}
