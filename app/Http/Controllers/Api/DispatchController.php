<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Dispatch;
use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DispatchController extends Controller
{
    public function index(Request $request)
    {
        $query = Dispatch::with([
            'reservation.customer:id,name,phone',
            'reservation.cast:id,name,rank_id',
            'reservation.cast.rank:id,name,color',
            'driver:id,name,car,phone,status',
        ]);

        if ($request->filled('date')) {
            $query->whereHas('reservation', fn ($q) => $q->whereDate('date', $request->date));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('driver_id')) {
            $query->where('driver_id', $request->driver_id);
        }

        return $query->orderBy('scheduled_at')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'reservation_id' => 'required|exists:reservations,id',
            'type'           => 'required|in:送り,迎え',
            'driver_id'      => 'nullable|exists:drivers,id',
            'scheduled_at'   => 'nullable|date',
            'note'           => 'nullable|string',
        ]);

        $dispatch = Dispatch::create($data);
        return response()->json($dispatch->load(['reservation.customer', 'driver']), 201);
    }

    public function update(Request $request, Dispatch $dispatch)
    {
        $data = $request->validate([
            'driver_id'     => 'nullable|exists:drivers,id',
            'status'        => 'sometimes|in:未配車,配車済,完了',
            'scheduled_at'  => 'nullable|date',
            'dispatched_at' => 'nullable|date',
            'completed_at'  => 'nullable|date',
            'note'          => 'nullable|string',
        ]);

        DB::transaction(function () use ($dispatch, $data) {
            $dispatch->update($data);

            // 配車済になったらドライバーを稼働中にする
            if (isset($data['status']) && $data['status'] === '配車済' && isset($data['driver_id'])) {
                Driver::where('id', $data['driver_id'])->update(['status' => '稼働中']);
            }

            // 完了になったらドライバーを待機中に戻す
            if (isset($data['status']) && $data['status'] === '完了' && $dispatch->driver_id) {
                $busyCount = Dispatch::where('driver_id', $dispatch->driver_id)
                    ->where('status', '配車済')
                    ->where('id', '!=', $dispatch->id)
                    ->count();

                if ($busyCount === 0) {
                    Driver::where('id', $dispatch->driver_id)->update(['status' => '待機中', 'return_at' => null]);
                }
            }
        });

        return response()->json($dispatch->load(['reservation.customer', 'driver']));
    }

    public function destroy(Dispatch $dispatch)
    {
        $dispatch->delete();
        return response()->json(null, 204);
    }
}
