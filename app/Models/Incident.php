<?php

namespace App\Models;

use App\Support\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['client_id', 'home_id', 'visit_id', 'reported_by', 'category', 'severity', 'occurred_at', 'description', 'immediate_actions', 'status', 'safeguarding_required'])]
class Incident extends Model
{
    use Auditable;

    public function client(): BelongsTo { return $this->belongsTo(Client::class); }
    public function home(): BelongsTo { return $this->belongsTo(Home::class); }
    public function visit(): BelongsTo { return $this->belongsTo(Visit::class); }
    public function reporter(): BelongsTo { return $this->belongsTo(User::class, 'reported_by'); }
    public function safeguardingCase(): HasOne { return $this->hasOne(SafeguardingCase::class); }

    protected function casts(): array
    {
        return ['occurred_at' => 'datetime', 'safeguarding_required' => 'boolean'];
    }
}
