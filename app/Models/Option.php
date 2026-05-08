<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Option extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'price', 'description'];

    public function ngCasts(): BelongsToMany
    {
        return $this->belongsToMany(Cast::class, 'cast_ng_options');
    }

    public function reservations(): BelongsToMany
    {
        return $this->belongsToMany(Reservation::class, 'reservation_options')->withPivot('price');
    }
}
