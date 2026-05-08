<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cast;
use Illuminate\Http\Request;

class CastController extends Controller
{
    public function index()
    {
        return Cast::with('rank:id,name,designation_fee,color,order')
            ->withCount(['reservations as today_count' => fn ($q) =>
                $q->whereDate('date', today())->where('reservation_status', '確定')
            ])
            ->orderByDesc('rank_id')
            ->get()
            ->map(function ($cast) {
                $cast->ng_option_ids = $cast->ngOptions()->pluck('option_id');
                return $cast;
            });
    }

    public function show(Cast $cast)
    {
        $cast->load('rank', 'ngOptions:id,name', 'ngCustomers:id,name,phone');
        return response()->json($cast);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'    => 'required|string|max:50',
            'age'     => 'nullable|integer|min:18|max:99',
            'status'  => 'in:待機中,稼働中,休み',
            'phone'   => 'nullable|string|max:20',
            'rank_id' => 'nullable|exists:ranks,id',
        ]);

        $cast = Cast::create($data);

        if ($request->has('ng_option_ids')) {
            $cast->ngOptions()->sync($request->ng_option_ids);
        }

        return response()->json($cast->load('rank'), 201);
    }

    public function update(Request $request, Cast $cast)
    {
        $data = $request->validate([
            'name'    => 'sometimes|string|max:50',
            'age'     => 'nullable|integer|min:18|max:99',
            'status'  => 'sometimes|in:待機中,稼働中,休み',
            'phone'   => 'nullable|string|max:20',
            'rank_id' => 'nullable|exists:ranks,id',
        ]);

        $cast->update($data);

        if ($request->has('ng_option_ids')) {
            $cast->ngOptions()->sync($request->ng_option_ids);
        }

        return response()->json($cast->load('rank'));
    }

    public function destroy(Cast $cast)
    {
        $cast->delete();
        return response()->json(null, 204);
    }

    // キャストのNG顧客同期
    public function syncNgCustomers(Request $request, Cast $cast)
    {
        $request->validate(['customer_ids' => 'array', 'customer_ids.*' => 'exists:customers,id']);
        $cast->ngCustomers()->sync($request->customer_ids ?? []);
        return response()->json(['customer_ids' => $cast->ngCustomers()->pluck('id')]);
    }
}
