<?php

namespace App\Models;

use App\Support\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['client_id', 'home_id', 'reviewed_by', 'decision_type', 'capacity_outcome', 'best_interest_status', 'advocate_status', 'review_date', 'next_review_date', 'evidence', 'notes'])]
class CapacityReview extends Model
{
    use Auditable;

    public function client(): BelongsTo { return $this->belongsTo(Client::class); }
    public function home(): BelongsTo { return $this->belongsTo(Home::class); }
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewed_by'); }

    protected function casts(): array
    {
        return ['review_date' => 'date', 'next_review_date' => 'date'];
    }
}
