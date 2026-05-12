<?php

namespace App\Models;

use App\Helpers\BandwidthFormatter;
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
        return BandwidthFormatter::format($this->in_bps ?? 0);
    }

    public function getOutMbpsAttribute(): string
    {
        return BandwidthFormatter::format($this->out_bps ?? 0);
    }
}
