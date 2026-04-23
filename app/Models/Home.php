<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'name',
    'registration_number',
    'care_type',
    'capacity',
    'phone',
    'email',
    'website',
    'address_line_1',
    'address_line_2',
    'city',
    'county',
    'postcode',
    'country',
    'status',
    'logo_path',
    'manager_id',
])]
class Home extends Model
{
    /** @use HasFactory<\Database\Factories\HomeFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * @return HasMany<Client, $this>
     */
    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    /**
     * @return HasMany<CarePlan, $this>
     */
    public function carePlans(): HasMany
    {
        return $this->hasMany(CarePlan::class);
    }

    /**
     * @return HasMany<Visit, $this>
     */
    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }

    public function logoUrl(): ?string
    {
        if ($this->logo_path === null) {
            return null;
        }

        return Storage::disk('public')->url($this->logo_path);
    }
}
