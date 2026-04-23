<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'home_id', 'job_title', 'phone', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * @return BelongsTo<Home, $this>
     */
    public function home(): BelongsTo
    {
        return $this->belongsTo(Home::class);
    }

    /**
     * @return BelongsToMany<Role, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    /**
     * @return BelongsToMany<Permission, $this>
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    /**
     * @return HasMany<Visit, $this>
     */
    public function assignedVisits(): HasMany
    {
        return $this->hasMany(Visit::class, 'assigned_user_id');
    }

    /**
     * @return \Illuminate\Support\Collection<int, Permission>
     */
    public function effectivePermissions()
    {
        return $this->permissions
            ->where('is_active', true)
            ->merge($this->roles->flatMap->permissions)
            ->where('is_active', true)
            ->unique('id')
            ->values();
    }

    public function hasPermission(string $permissionName): bool
    {
        return $this->is_active !== false && ($this->permissions()->where('name', $permissionName)->where('is_active', true)->exists()
            || $this->roles()
                ->where('is_active', true)
                ->whereHas('permissions', fn ($query) => $query->where('name', $permissionName))
                ->whereHas('permissions', fn ($query) => $query->where('is_active', true))
                ->exists());
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }
}
