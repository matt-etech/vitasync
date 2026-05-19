<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFamilyMemberRequest;
use App\Http\Requests\UpdateFamilyMemberRequest;
use App\Models\Client;
use App\Models\FamilyMember;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class FamilyMemberController extends Controller
{
    public function index(): View
    {
        return view('family-members.index', [
            'familyMembers' => FamilyMember::with([
                'auditLogs.actor',
                'client.home',
                'clients.home',
                'loginCreator',
            ])->orderBy('name')->get(),
            'newFamilyMember' => new FamilyMember(['is_active' => true]),
            'clients' => Client::with('home')->where('status', 'active')->orderBy('last_name')->orderBy('first_name')->get(),
            'accessFields' => FamilyMember::ACCESS_FIELDS,
            'accessLabels' => FamilyMember::accessLabels(),
        ]);
    }

    public function store(StoreFamilyMemberRequest $request, AuditLogger $auditLogger): RedirectResponse
    {
        $validated = $request->validated();
        $client = Client::findOrFail($validated['client_id']);
        $familyMember = FamilyMember::create(array_merge($this->payload($validated, $client), [
            'login_created_at' => now(),
            'login_created_by' => Auth::id(),
        ]));
        $assignedClients = $this->syncClients($familyMember, $validated, $client->id);

        $auditLogger->log('family.login_account_created', [
            'auditable' => $familyMember,
            'event' => 'FamilyLoginAccount',
            'metadata' => [
                'family_member_id' => $familyMember->id,
                'client_id' => $familyMember->client_id,
                'home_id' => $familyMember->home_id,
                'email' => $familyMember->email,
                'login_created_at' => $familyMember->login_created_at,
                'login_created_by' => $familyMember->login_created_by,
            ],
        ]);
        $this->logAccessChange($auditLogger, $familyMember, [], $familyMember->accessSummary());
        $this->logClientAssignments($auditLogger, $familyMember, [], $assignedClients);

        return redirect()->route('family-members.index')->with('status', 'Family login account created and saved.');
    }

    public function update(UpdateFamilyMemberRequest $request, FamilyMember $familyMember, AuditLogger $auditLogger): RedirectResponse
    {
        $oldAccess = $familyMember->accessSummary();
        $oldClients = $familyMember->clients()->pluck('clients.id')->all();
        $validated = $request->validated();
        $client = Client::findOrFail($validated['client_id']);

        $familyMember->update($this->payload($validated, $client, false));
        $assignedClients = $this->syncClients($familyMember, $validated, $client->id);
        $this->logAccessChange($auditLogger, $familyMember, $oldAccess, $familyMember->fresh()->accessSummary());
        $this->logClientAssignments($auditLogger, $familyMember, $oldClients, $assignedClients);

        return redirect()->route('family-members.index')->with('status', 'Family member access updated.');
    }

    /**
     * @param array<string, mixed> $validated
     */
    private function syncClients(FamilyMember $familyMember, array $validated, int $primaryClientId): array
    {
        $clientIds = collect($validated['client_ids'] ?? [])
            ->map(fn (mixed $id): int => (int) $id)
            ->push($primaryClientId)
            ->unique()
            ->values();

        $familyMember->clients()->sync(
            $clientIds->mapWithKeys(fn (int $clientId): array => [
                $clientId => ['is_primary' => $clientId === $primaryClientId],
            ])->all()
        );

        return $clientIds->all();
    }

    public function destroy(FamilyMember $familyMember, AuditLogger $auditLogger): RedirectResponse
    {
        $oldAccess = $familyMember->accessSummary();
        $familyMember->update(['is_active' => ! $familyMember->is_active]);

        $this->logAccessChange($auditLogger, $familyMember, $oldAccess, $familyMember->fresh()->accessSummary());

        return redirect()->route('family-members.index')->with('status', $familyMember->is_active ? 'Family access activated.' : 'Family access disabled.');
    }

    /**
     * @param array<string, mixed> $validated
     * @return array<string, mixed>
     */
    private function payload(array $validated, Client $client, bool $requirePassword = true): array
    {
        $payload = Arr::only($validated, ['client_id', 'name', 'relationship', 'email', 'phone', 'access_notes']);
        $payload['home_id'] = $client->home_id;
        $payload['is_active'] = (bool) ($validated['is_active'] ?? false);

        foreach (FamilyMember::ACCESS_FIELDS as $field) {
            $payload[$field] = (bool) ($validated[$field] ?? false);
        }

        if ($requirePassword || filled($validated['password'] ?? null)) {
            $payload['password'] = $validated['password'];
        }

        return $payload;
    }

    /**
     * @param array<string, bool> $oldAccess
     * @param array<string, bool> $newAccess
     */
    private function logAccessChange(AuditLogger $auditLogger, FamilyMember $familyMember, array $oldAccess, array $newAccess): void
    {
        $auditLogger->log('family.access_permissions_updated', [
            'auditable' => $familyMember,
            'event' => 'FamilyAccess',
            'old_values' => $oldAccess,
            'new_values' => $newAccess,
            'metadata' => [
                'family_member_id' => $familyMember->id,
                'client_id' => $familyMember->client_id,
                'home_id' => $familyMember->home_id,
            ],
        ]);
    }

    /**
     * @param list<int> $oldClientIds
     * @param list<int> $newClientIds
     */
    private function logClientAssignments(AuditLogger $auditLogger, FamilyMember $familyMember, array $oldClientIds, array $newClientIds): void
    {
        $auditLogger->log('family.client_access_updated', [
            'auditable' => $familyMember,
            'event' => 'Family client access',
            'friendly_action' => 'Changed client access for',
            'friendly_subject' => $familyMember->name,
            'friendly_actor' => Auth::user()?->name ?? 'System',
            'friendly_summary' => 'Client access was updated for '.$familyMember->name.'.',
            'old_values' => [
                'Client IDs they could see before' => $oldClientIds,
            ],
            'new_values' => [
                'Client IDs they can see now' => $newClientIds,
            ],
            'metadata' => [
                'Family member' => $familyMember->name,
                'Primary client ID' => $familyMember->client_id,
                'Home ID' => $familyMember->home_id,
            ],
        ]);
    }
}
