<?php

namespace App\Models;

use App\Support\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['client_id', 'home_id', 'incident_id', 'opened_by', 'concern_type', 'risk_level', 'status', 'opened_at', 'referred_at', 'summary', 'actions_taken'])]
class SafeguardingCase extends Model
{
    use Auditable;

    public function client(): BelongsTo { return $this->belongsTo(Client::class); }
    public function home(): BelongsTo { return $this->belongsTo(Home::class); }
    public function incident(): BelongsTo { return $this->belongsTo(Incident::class); }
    public function opener(): BelongsTo { return $this->belongsTo(User::class, 'opened_by'); }

    protected function casts(): array
    {
        return ['opened_at' => 'datetime', 'referred_at' => 'datetime'];
    }
}
