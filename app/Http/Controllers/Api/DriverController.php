<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use Illuminate\Http\Request;

class DriverController extends Controller
{
    public function index()
    {
        return Driver::withCount(['dispatches as today_count' => fn ($q) =>
            $q->whereHas('reservation', fn ($r) => $r->whereDate('date', today()))
              ->where('status', '完了')
        ])->orderBy('name')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:50',
            'status'    => 'in:待機中,稼働中,休み',
            'car'       => 'nullable|string|max:100',
            'phone'     => 'nullable|string|max:20',
            'return_at' => 'nullable|date_format:H:i',
        ]);

        return response()->json(Driver::create($data), 201);
    }

    public function update(Request $request, Driver $driver)
    {
        $data = $request->validate([
            'name'      => 'sometimes|string|max:50',
            'status'    => 'sometimes|in:待機中,稼働中,休み',
            'car'       => 'nullable|string|max:100',
            'phone'     => 'nullable|string|max:20',
            'return_at' => 'nullable|date_format:H:i',
        ]);

        $driver->update($data);
        return response()->json($driver);
    }

    public function destroy(Driver $driver)
    {
        $driver->delete();
        return response()->json(null, 204);
    }
}
