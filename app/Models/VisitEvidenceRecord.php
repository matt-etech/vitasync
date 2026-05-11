<?php

namespace App\Models;

use App\Support\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'visit_id',
    'client_id',
    'carer_id',
    'evidence_type',
    'label',
    'file_name',
    'metadata',
    'captured_at',
])]
class VisitEvidenceRecord extends Model
{
    use Auditable;

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function carer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'carer_id');
    }

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'captured_at' => 'datetime',
        ];
    }
}
