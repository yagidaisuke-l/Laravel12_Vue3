<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Codeword extends Model
{
    use SoftDeletes;

    protected $fillable = ['site_name', 'word', 'discount_type', 'discount_value', 'description', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
