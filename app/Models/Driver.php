<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Driver extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'status', 'car', 'phone', 'return_at'];

    public function dispatches(): HasMany
    {
        return $this->hasMany(Dispatch::class);
    }

    // 本日の件数（集計）
    public function getTodayCountAttribute(): int
    {
        return $this->dispatches()
            ->whereHas('reservation', fn ($q) => $q->whereDate('date', today()))
            ->where('status', '完了')
            ->count();
    }
}
