<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogActivity extends Model
{
    //
    use HasFactory;

    protected $fillable = [
        'opd_id',
        'timestamp',
        'in_bps',
        'out_bps',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
    ];

    public function opd(): BelongsTo
    {
        return $this->belongsTo(Opd::class);
    }

    public function getInMbpsAttribute(): string
    {
        return number_format(($this->in_bps ?? 0) / 1_000_000, 2) . ' Mbps';
    }

    public function getOutMbpsAttribute(): string
    {
        return number_format(($this->out_bps ?? 0) / 1_000_000, 2) . ' Mbps';
    }
}
