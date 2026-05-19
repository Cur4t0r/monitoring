<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Opd extends Model
{
    //
    use HasFactory;

    protected $fillable = [
        'nama_opd',
        'nama_perangkat',
        'ip_address',
        'interface',
        'keterangan',
    ];

    public function logActivities(): HasMany
    {
        return $this->hasMany(LogActivity::class);
    }

    public function maintenances(): BelongsToMany
    {
        return $this->belongsToMany(Maintenance::class, 'maintenance_opd');
    }

    public function systemAlerts(): HasMany
    {
        return $this->hasMany(SystemAlert::class);
    }
}
