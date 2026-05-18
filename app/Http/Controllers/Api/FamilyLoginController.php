<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\FamilyLoginRequest;
use App\Models\FamilyMember;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class FamilyLoginController extends Controller
{
    public function __invoke(FamilyLoginRequest $request, AuditLogger $auditLogger): JsonResponse
    {
        $familyMember = FamilyMember::with(['client.home', 'clients.home'])->where('email', $request->validated('email'))->first();

        if (! $familyMember || ! Hash::check($request->validated('password'), $familyMember->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials do not match a family access record.'],
            ]);
        }

        if (! $familyMember->is_active) {
            abort(403, 'This family access account is not active.');
        }

        $familyMember->update(['last_login_at' => now()]);

        $auditLogger->log('family.login', [
            'auditable' => $familyMember,
            'event' => 'FamilyLogin',
            'metadata' => [
                'family_member_id' => $familyMember->id,
            'client_id' => $familyMember->client_id,
            'client_ids' => $familyMember->clients->pluck('id')->all(),
            'device_timezone' => $request->validated('device_timezone'),
                'device_datetime' => $request->validated('device_datetime'),
            ],
        ]);

        return response()->json([
            'message' => 'Family login verified.',
            'family_member' => $this->payload($familyMember),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(FamilyMember $familyMember): array
    {
        $assignedClients = $familyMember->clients->isNotEmpty()
            ? $familyMember->clients->sortByDesc(fn ($client): bool => (bool) $client->pivot->is_primary)->values()
            : collect([$familyMember->client]);

        return [
            'id' => $familyMember->id,
            'name' => $familyMember->name,
            'email' => $familyMember->email,
            'relationship' => $familyMember->relationship,
            'client' => [
                'id' => $familyMember->client->id,
                'name' => $familyMember->client->fullName(),
                'home_name' => $familyMember->client->home?->name,
            ],
            'clients' => $assignedClients
                ->map(fn ($client): array => [
                    'id' => $client->id,
                    'name' => $client->fullName(),
                    'home_name' => $client->home?->name,
                ])
                ->values()
                ->all(),
            'permissions' => $familyMember->accessSummary(),
        ];
    }
}
