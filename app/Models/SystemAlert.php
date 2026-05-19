<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemAlert extends Model
{
    //
    use HasFactory;

    protected $fillable = [
        'message',
        'severity',
        'status',
        'opd_id',
        'occurred_at',
        'acknowledged_at',
        'acknowledged_by',
    ];

    protected $casts = [
        'occurred_at'     => 'datetime',
        'acknowledged_at' => 'datetime',
    ];

    public function opd(): BelongsTo
    {
        return $this->belongsTo(Opd::class);
    }

    public function acknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
