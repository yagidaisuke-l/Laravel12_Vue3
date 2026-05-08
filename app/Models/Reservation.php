<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reservation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'customer_id', 'cast_id', 'codeword_id',
        'date', 'time', 'duration',
        'area', 'address',
        'base_price', 'designation_fee', 'options_total_price', 'transport_fee', 'discount_amount', 'total_price',
        'reservation_status', 'note',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function cast(): BelongsTo
    {
        return $this->belongsTo(Cast::class);
    }

    public function codeword(): BelongsTo
    {
        return $this->belongsTo(Codeword::class);
    }

    public function options(): BelongsToMany
    {
        return $this->belongsToMany(Option::class, 'reservation_options')->withPivot('price');
    }

    public function dispatches(): HasMany
    {
        return $this->hasMany(Dispatch::class);
    }

    // time を HH:MM 形式で返す
    public function getTimeAttribute(string $value): string
    {
        return substr($value, 0, 5);
    }
}
