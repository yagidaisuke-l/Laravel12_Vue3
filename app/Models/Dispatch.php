<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Dispatch extends Model
{
    protected $fillable = [
        'reservation_id', 'driver_id', 'type', 'status',
        'scheduled_at', 'dispatched_at', 'completed_at', 'note',
    ];

    protected $casts = [
        'scheduled_at'  => 'datetime',
        'dispatched_at' => 'datetime',
        'completed_at'  => 'datetime',
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }
}
