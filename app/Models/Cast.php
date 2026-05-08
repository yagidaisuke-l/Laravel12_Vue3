<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Cast extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'age', 'status', 'phone', 'rank_id'];

    public function rank(): BelongsTo
    {
        return $this->belongsTo(Rank::class);
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    // キャストがNGにしているオプション
    public function ngOptions(): BelongsToMany
    {
        return $this->belongsToMany(Option::class, 'cast_ng_options');
    }

    // キャストがNGにしている顧客
    public function ngCustomers(): BelongsToMany
    {
        return $this->belongsToMany(Customer::class, 'cast_ng_customers');
    }

    // 本日の件数（集計）
    public function getTodayCountAttribute(): int
    {
        return $this->reservations()
            ->whereDate('date', today())
            ->where('reservation_status', '確定')
            ->count();
    }
}
