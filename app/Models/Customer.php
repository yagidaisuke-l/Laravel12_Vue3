<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Customer extends Model
{
    use SoftDeletes;

    protected $fillable = ['phone', 'name', 'status', 'memo', 'last_visit'];

    protected $casts = [
        'last_visit' => 'date',
    ];

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    // 顧客がNGにしているキャスト
    public function ngCasts(): BelongsToMany
    {
        return $this->belongsToMany(Cast::class, 'customer_ng_casts');
    }

    // 来店回数（予約から集計）
    public function getVisitCountAttribute(): int
    {
        return $this->reservations()->where('reservation_status', '確定')->count();
    }

    // 指名履歴（キャスト別件数）
    public function getCastHistoryAttribute(): array
    {
        return $this->reservations()
            ->where('reservation_status', '確定')
            ->with('cast:id,name')
            ->get()
            ->groupBy('cast_id')
            ->map(fn ($items) => [
                'castName' => $items->first()->cast->name ?? '',
                'count'    => $items->count(),
            ])
            ->values()
            ->sortByDesc('count')
            ->values()
            ->toArray();
    }
}
