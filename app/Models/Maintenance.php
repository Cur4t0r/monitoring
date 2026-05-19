<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Maintenance extends Model
{
    //
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'scheduled_at',
        'duration_minutes',
        'status',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
    ];

    public function opds(): BelongsToMany
    {
        return $this->belongsToMany(Opd::class, 'maintenance_opd');
    }

    /** Format durasi menjadi string yang mudah dibaca, misal "2j 30m" */
    public function getFormattedDurationAttribute(): string
    {
        $hours   = intdiv($this->duration_minutes, 60);
        $minutes = $this->duration_minutes % 60;

        if ($hours > 0 && $minutes > 0) {
            return "{$hours}j {$minutes}m";
        }

        return $hours > 0 ? "{$hours}j" : "{$minutes}m";
    }
}
