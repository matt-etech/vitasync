<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CarerFamilyMessageRequest;
use App\Models\Client;
use App\Models\FamilyPortalMessage;
use App\Models\User;
use App\Models\Visit;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;

class CarerFamilyMessageController extends Controller
{
    public function __invoke(CarerFamilyMessageRequest $request, AuditLogger $auditLogger): JsonResponse
    {
        $carer = User::query()->with('roles')->findOrFail($request->integer('carer_id'));

        if (! $carer->is_active || ! $carer->roles->contains(fn ($role): bool => $role->name === 'Carer' && $role->is_active)) {
            abort(403, 'This endpoint is only available to active carers.');
        }

        $validated = $request->validated();
        $client = Client::query()->with('home')->findOrFail((int) $validated['client_id']);

        if (! $this->carerCanMessageClient($carer, $client)) {
            abort(403, 'This client is not assigned to the carer.');
        }

        $message = FamilyPortalMessage::create([
            'client_id' => $client->id,
            'sent_by_user_id' => $carer->id,
            'subject' => $validated['subject'],
            'message' => $validated['message'],
            'visible_to_family' => true,
            'sent_at' => now(),
        ]);

        $auditLogger->log('family.staff_message_sent', [
            'actor_id' => $carer->id,
            'auditable' => $message,
            'event' => 'Family message',
            'friendly_action' => 'sent a message to family',
            'friendly_subject' => $client->fullName(),
            'friendly_summary' => "{$carer->name} sent a family portal message about {$client->fullName()}.",
            'metadata' => [
                'Client' => $client->fullName(),
                'Home' => $client->home?->name,
                'Carer' => $carer->name,
                'Subject' => $message->subject,
                'Shown in family portal' => 'Yes, when Messages from staff is allowed for the family member',
            ],
        ]);

        return response()->json([
            'status' => 'sent',
            'message_id' => $message->id,
            'sent_at' => $message->sent_at?->format('d/m/Y H:i'),
        ]);
    }

    private function carerCanMessageClient(User $carer, Client $client): bool
    {
        return Visit::query()
            ->where('assigned_user_id', $carer->id)
            ->where('client_id', $client->id)
            ->exists();
    }
}
