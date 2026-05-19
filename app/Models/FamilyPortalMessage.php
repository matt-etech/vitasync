<?php

namespace App\Models;

use App\Support\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'client_id',
    'sent_by_user_id',
    'subject',
    'message',
    'visible_to_family',
    'sent_at',
])]
class FamilyPortalMessage extends Model
{
    use Auditable;

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by_user_id');
    }

    protected function casts(): array
    {
        return [
            'visible_to_family' => 'boolean',
            'sent_at' => 'datetime',
        ];
    }
}
