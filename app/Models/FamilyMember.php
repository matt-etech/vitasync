<?php

namespace App\Models;

use App\Support\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable([
    'client_id',
    'home_id',
    'name',
    'relationship',
    'email',
    'phone',
    'password',
    'is_active',
    'can_view_care_updates',
    'can_view_medication',
    'can_view_invoices',
    'can_receive_incident_alerts',
    'can_view_appointments',
    'can_view_visits',
    'can_upload_documents',
    'can_view_staff_messages',
    'can_view_shared_documents',
    'can_view_sensitive_documents',
    'can_view_safeguarding',
    'last_login_at',
    'login_created_at',
    'login_created_by',
    'access_notes',
])]
#[Hidden(['password'])]
class FamilyMember extends Model
{
    use Auditable;

    public const ACCESS_FIELDS = [
        'can_view_care_updates',
        'can_view_medication',
        'can_view_invoices',
        'can_receive_incident_alerts',
        'can_view_appointments',
        'can_view_visits',
        'can_upload_documents',
        'can_view_staff_messages',
        'can_view_shared_documents',
        'can_view_sensitive_documents',
        'can_view_safeguarding',
    ];

    /**
     * @return array<string, array{label: string, help: string}>
     */
    public static function accessLabels(): array
    {
        return [
            'can_view_care_updates' => ['label' => 'View care updates', 'help' => 'Basic care plan summary approved by the care team.'],
            'can_view_medication' => ['label' => 'View medication / MAR', 'help' => 'Medication support and family-visible administration history.'],
            'can_view_invoices' => ['label' => 'View invoices', 'help' => 'Invoices/statements when billing is enabled.'],
            'can_receive_incident_alerts' => ['label' => 'Receive incident alerts', 'help' => 'Only manager-approved incident notifications.'],
            'can_view_appointments' => ['label' => 'View appointments', 'help' => 'Scheduled appointments and visit bookings.'],
            'can_view_visits' => ['label' => 'View visits', 'help' => 'Visit times and care note summaries.'],
            'can_upload_documents' => ['label' => 'Upload documents', 'help' => 'Upload family-supplied documents for the care team.'],
            'can_view_staff_messages' => ['label' => 'Messages from staff', 'help' => 'Read messages sent by staff.'],
            'can_view_shared_documents' => ['label' => 'View shared documents', 'help' => 'Documents explicitly shared with them.'],
            'can_view_sensitive_documents' => ['label' => 'Sensitive documents', 'help' => 'Sensitive shared documents only when granted.'],
            'can_view_safeguarding' => ['label' => 'Safeguarding access', 'help' => 'Approved safeguarding summary only when granted.'],
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function clients(): BelongsToMany
    {
        return $this->belongsToMany(Client::class, 'family_member_clients')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    public function home(): BelongsTo
    {
        return $this->belongsTo(Home::class);
    }

    public function loginCreator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'login_created_by');
    }

    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'auditable')->latest();
    }

    public function canAccess(string $permission): bool
    {
        return $this->is_active && (bool) $this->{$permission};
    }

    public function accessSummary(): array
    {
        return collect(self::ACCESS_FIELDS)
            ->mapWithKeys(fn (string $field): array => [$field => (bool) $this->{$field}])
            ->all();
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'can_view_care_updates' => 'boolean',
            'can_view_medication' => 'boolean',
            'can_view_invoices' => 'boolean',
            'can_receive_incident_alerts' => 'boolean',
            'can_view_appointments' => 'boolean',
            'can_view_visits' => 'boolean',
            'can_upload_documents' => 'boolean',
            'can_view_staff_messages' => 'boolean',
            'can_view_shared_documents' => 'boolean',
            'can_view_sensitive_documents' => 'boolean',
            'can_view_safeguarding' => 'boolean',
            'last_login_at' => 'datetime',
            'login_created_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
