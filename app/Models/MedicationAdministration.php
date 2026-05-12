<?php

namespace App\Models;

use App\Support\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['medication_id', 'client_id', 'visit_id', 'administered_by', 'outcome', 'administered_at', 'notes'])]
class MedicationAdministration extends Model
{
    use Auditable;

    public function medication(): BelongsTo { return $this->belongsTo(Medication::class); }
    public function client(): BelongsTo { return $this->belongsTo(Client::class); }
    public function visit(): BelongsTo { return $this->belongsTo(Visit::class); }
    public function administrator(): BelongsTo { return $this->belongsTo(User::class, 'administered_by'); }

    protected function casts(): array
    {
        return ['administered_at' => 'datetime'];
    }
}
