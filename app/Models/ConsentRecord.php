<?php

namespace App\Models;

use App\Support\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['client_id', 'home_id', 'recorded_by', 'consent_type', 'decision', 'given_by', 'recorded_at', 'review_date', 'evidence', 'notes'])]
class ConsentRecord extends Model
{
    use Auditable;

    public function client(): BelongsTo { return $this->belongsTo(Client::class); }
    public function home(): BelongsTo { return $this->belongsTo(Home::class); }
    public function recorder(): BelongsTo { return $this->belongsTo(User::class, 'recorded_by'); }

    protected function casts(): array
    {
        return ['recorded_at' => 'datetime', 'review_date' => 'date'];
    }
}
