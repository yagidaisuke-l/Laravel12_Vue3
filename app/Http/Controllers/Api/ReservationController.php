<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\Dispatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReservationController extends Controller
{
    public function index(Request $request)
    {
        $query = Reservation::with([
            'customer:id,name,phone',
            'cast:id,name,rank_id',
            'cast.rank:id,name,color',
            'codeword:id,site_name,word',
            'options:id,name,price',
            'dispatches:id,reservation_id,type,status,driver_id',
            'dispatches.driver:id,name',
        ]);

        if ($request->filled('date')) {
            $query->whereDate('date', $request->date);
        }

        if ($request->filled('from') && $request->filled('to')) {
            $query->whereBetween('date', [$request->from, $request->to]);
        }

        if ($request->filled('cast_id')) {
            $query->where('cast_id', $request->cast_id);
        }

        if ($request->filled('status')) {
            $query->where('reservation_status', $request->status);
        }

        return $query->orderBy('date')->orderBy('time')->paginate(50);
    }

    public function show(Reservation $reservation)
    {
        $reservation->load([
            'customer', 'cast.rank', 'codeword', 'options',
            'dispatches.driver',
        ]);

        return response()->json($reservation);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_id'        => 'required|exists:customers,id',
            'cast_id'            => 'required|exists:casts,id',
            'codeword_id'        => 'nullable|exists:codewords,id',
            'date'               => 'required|date',
            'time'               => 'required|date_format:H:i',
            'duration'           => 'required|integer|min:30',
            'area'               => 'nullable|string|max:100',
            'address'            => 'nullable|string|max:255',
            'base_price'         => 'required|integer|min:0',
            'designation_fee'    => 'required|integer|min:0',
            'options_total_price'=> 'required|integer|min:0',
            'transport_fee'      => 'required|integer|min:0',
            'discount_amount'    => 'required|integer|min:0',
            'total_price'        => 'required|integer|min:0',
            'reservation_status' => 'in:仮予約,確定',
            'note'               => 'nullable|string',
            'option_ids'         => 'array',
            'option_ids.*'       => 'exists:options,id',
        ]);

        $reservation = DB::transaction(function () use ($data, $request) {
            $reservation = Reservation::create($data);

            // オプション登録（予約時点の価格を保存）
            if (!empty($data['option_ids'])) {
                $syncData = [];
                foreach ($request->option_ids as $optId) {
                    $opt = \App\Models\Option::find($optId);
                    $syncData[$optId] = ['price' => $opt?->price ?? 0];
                }
                $reservation->options()->sync($syncData);
            }

            // 配車レコードを自動生成（送り）
            Dispatch::create([
                'reservation_id' => $reservation->id,
                'type'           => '送り',
                'status'         => '未配車',
                'scheduled_at'   => $data['date'] . ' ' . $data['time'],
            ]);

            // 顧客の最終来店日を更新
            $reservation->customer->update(['last_visit' => $data['date']]);

            return $reservation;
        });

        return response()->json($reservation->load(['customer', 'cast.rank', 'options']), 201);
    }

    public function update(Request $request, Reservation $reservation)
    {
        $data = $request->validate([
            'cast_id'            => 'sometimes|exists:casts,id',
            'codeword_id'        => 'nullable|exists:codewords,id',
            'date'               => 'sometimes|date',
            'time'               => 'sometimes|date_format:H:i',
            'duration'           => 'sometimes|integer|min:30',
            'area'               => 'nullable|string|max:100',
            'address'            => 'nullable|string|max:255',
            'base_price'         => 'sometimes|integer|min:0',
            'designation_fee'    => 'sometimes|integer|min:0',
            'options_total_price'=> 'sometimes|integer|min:0',
            'transport_fee'      => 'sometimes|integer|min:0',
            'discount_amount'    => 'sometimes|integer|min:0',
            'total_price'        => 'sometimes|integer|min:0',
            'reservation_status' => 'sometimes|in:仮予約,確定,キャンセル',
            'note'               => 'nullable|string',
            'option_ids'         => 'sometimes|array',
            'option_ids.*'       => 'exists:options,id',
        ]);

        DB::transaction(function () use ($reservation, $data, $request) {
            $reservation->update($data);

            if ($request->has('option_ids')) {
                $syncData = [];
                foreach ($request->option_ids as $optId) {
                    $opt = \App\Models\Option::find($optId);
                    $syncData[$optId] = ['price' => $opt?->price ?? 0];
                }
                $reservation->options()->sync($syncData);
            }
        });

        return response()->json($reservation->load(['customer', 'cast.rank', 'options']));
    }

    public function destroy(Reservation $reservation)
    {
        $reservation->delete();
        return response()->json(null, 204);
    }
}
