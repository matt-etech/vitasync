<?php

namespace App\Models;

use App\Support\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['client_id', 'home_id', 'name', 'dose', 'route', 'frequency', 'support_level', 'status', 'start_date', 'end_date', 'instructions'])]
class Medication extends Model
{
    use Auditable;

    public function client(): BelongsTo { return $this->belongsTo(Client::class); }
    public function home(): BelongsTo { return $this->belongsTo(Home::class); }
    public function administrations(): HasMany { return $this->hasMany(MedicationAdministration::class); }

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date'];
    }
}
