<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\FamilyChangePasswordRequest;
use App\Models\FamilyMember;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class FamilyChangePasswordController extends Controller
{
    public function __invoke(FamilyChangePasswordRequest $request, AuditLogger $auditLogger): JsonResponse
    {
        $familyMember = FamilyMember::query()->findOrFail($request->integer('family_member_id'));

        if (! $familyMember->is_active) {
            abort(403, 'This family access account is not active.');
        }

        if (! Hash::check($request->string('current_password')->toString(), $familyMember->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password is not correct.'],
            ]);
        }

        $familyMember->forceFill([
            'password' => Hash::make($request->string('password')->toString()),
        ])->save();

        $auditLogger->log('family.password_changed', [
            'auditable' => $familyMember,
            'event' => 'FamilyMember',
            'metadata' => [
                'family_member_id' => $familyMember->id,
                'client_id' => $familyMember->client_id,
                'changed_by' => 'family_mobile_profile',
            ],
        ]);

        return response()->json([
            'message' => 'Password changed.',
        ]);
    }
}
