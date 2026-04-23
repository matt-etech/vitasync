<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'home_id',
    'client_id',
    'care_plan_id',
    'assigned_user_id',
    'title',
    'scheduled_start_at',
    'scheduled_end_at',
    'status',
    'check_in_at',
    'check_out_at',
    'notes',
])]
class Visit extends Model
{
    use HasFactory;

    /**
     * @return BelongsTo<Home, $this>
     */
    public function home(): BelongsTo
    {
        return $this->belongsTo(Home::class);
    }

    /**
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * @return BelongsTo<CarePlan, $this>
     */
    public function carePlan(): BelongsTo
    {
        return $this->belongsTo(CarePlan::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignedWorker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function durationLabel(): string
    {
        if ($this->scheduled_start_at === null || $this->scheduled_end_at === null) {
            return 'Not scheduled';
        }

        return $this->scheduled_start_at->format('Y-m-d H:i').' to '.$this->scheduled_end_at->format('H:i');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scheduled_start_at' => 'datetime',
            'scheduled_end_at' => 'datetime',
            'check_in_at' => 'datetime',
            'check_out_at' => 'datetime',
        ];
    }
}
