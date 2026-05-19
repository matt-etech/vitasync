<?php

namespace App\Models;

use App\Support\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'client_id',
    'uploaded_by_family_member_id',
    'uploaded_by_user_id',
    'display_name',
    'original_filename',
    'file_path',
    'mime_type',
    'file_size',
    'category',
    'is_sensitive',
    'shared_with_family',
    'uploaded_at',
])]
class FamilyPortalDocument extends Model
{
    use Auditable;

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function familyMember(): BelongsTo
    {
        return $this->belongsTo(FamilyMember::class, 'uploaded_by_family_member_id');
    }

    public function staffUploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    protected function casts(): array
    {
        return [
            'is_sensitive' => 'boolean',
            'shared_with_family' => 'boolean',
            'uploaded_at' => 'datetime',
        ];
    }
}
