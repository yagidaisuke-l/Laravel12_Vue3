<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shift extends Model
{
    protected $fillable = ['cast_id', 'date', 'start_time', 'end_time', 'status', 'absent_note', 'note'];

    protected $casts = [
        'date' => 'date:Y-m-d',
    ];

    public function getStartTimeAttribute(string $value): string { return substr($value, 0, 5); }
    public function getEndTimeAttribute(string $value): string   { return substr($value, 0, 5); }

    public function cast(): BelongsTo
    {
        return $this->belongsTo(Cast::class);
    }
}
