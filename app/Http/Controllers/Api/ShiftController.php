<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShiftController extends Controller
{
    public function index(Request $request)
    {
        $query = Shift::with('cast:id,name,rank_id')->with('cast.rank:id,name,color,order');

        if ($request->filled('date')) {
            $query->where('date', $request->date);
        }

        if ($request->filled('from') && $request->filled('to')) {
            $query->whereBetween('date', [$request->from, $request->to]);
        }

        if ($request->filled('cast_id')) {
            $query->where('cast_id', $request->cast_id);
        }

        return $query->orderBy('date')->orderBy('start_time')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'cast_id'    => 'required|exists:casts,id',
            'date'       => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time'   => 'required|date_format:H:i',
            'note'       => 'nullable|string|max:255',
        ]);

        $shift = Shift::updateOrCreate(
            ['cast_id' => $data['cast_id'], 'date' => $data['date']],
            array_merge($data, ['status' => '出勤予定', 'absent_note' => null])
        );

        return response()->json($shift, 201);
    }

    // 一括登録（日付範囲 × 曜日指定）
    public function bulkStore(Request $request)
    {
        $request->validate([
            'cast_id'    => 'required|exists:casts,id',
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'dows'       => 'required|array',
            'dows.*'     => 'integer|between:0,6',
            'start_time' => 'required|date_format:H:i',
            'end_time'   => 'required|date_format:H:i',
            'overwrite'  => 'boolean',
            'note'       => 'nullable|string|max:255',
        ]);

        $dates   = [];
        $current = new \DateTime($request->start_date);
        $end     = new \DateTime($request->end_date);

        while ($current <= $end) {
            if (in_array((int)$current->format('w'), $request->dows)) {
                $dates[] = $current->format('Y-m-d');
            }
            $current->modify('+1 day');
        }

        $count = 0;
        DB::transaction(function () use ($request, $dates, &$count) {
            foreach ($dates as $date) {
                $exists = Shift::where('cast_id', $request->cast_id)->where('date', $date)->exists();
                if ($exists && !$request->boolean('overwrite')) {
                    continue;
                }
                Shift::updateOrCreate(
                    ['cast_id' => $request->cast_id, 'date' => $date],
                    [
                        'start_time'  => $request->start_time,
                        'end_time'    => $request->end_time,
                        'status'      => '出勤予定',
                        'absent_note' => null,
                        'note'        => $request->note,
                    ]
                );
                $count++;
            }
        });

        return response()->json(['registered' => $count]);
    }

    public function update(Request $request, Shift $shift)
    {
        $data = $request->validate([
            'start_time'  => 'sometimes|date_format:H:i',
            'end_time'    => 'sometimes|date_format:H:i',
            'status'      => 'sometimes|in:出勤予定,体調不良,無断欠勤,その他欠勤',
            'absent_note' => 'nullable|string|max:255',
            'note'        => 'nullable|string|max:255',
        ]);

        $shift->update($data);
        return response()->json($shift);
    }

    // 指定日以降の同キャストを一括欠勤
    public function markAbsentFrom(Request $request)
    {
        $request->validate([
            'cast_id'     => 'required|exists:casts,id',
            'from_date'   => 'required|date',
            'status'      => 'required|in:体調不良,無断欠勤,その他欠勤',
            'absent_note' => 'nullable|string|max:255',
        ]);

        $count = Shift::where('cast_id', $request->cast_id)
            ->where('date', '>=', $request->from_date)
            ->update([
                'status'      => $request->status,
                'absent_note' => $request->absent_note,
            ]);

        return response()->json(['updated' => $count]);
    }

    public function destroy(Shift $shift)
    {
        $shift->delete();
        return response()->json(null, 204);
    }
}
