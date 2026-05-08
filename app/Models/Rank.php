<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rank extends Model
{
    protected $fillable = ['name', 'designation_fee', 'color', 'order'];

    public function castMembers(): HasMany
    {
        return $this->hasMany(Cast::class);
    }
}
