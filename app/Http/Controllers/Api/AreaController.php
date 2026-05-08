<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Area;
use Illuminate\Http\Request;

class AreaController extends Controller
{
    public function index()
    {
        return Area::orderBy('area')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'area' => 'required|string|max:100|unique:areas,area',
            'fee'  => 'required|integer|min:0',
        ]);

        return response()->json(Area::create($data), 201);
    }

    public function update(Request $request, Area $area)
    {
        $data = $request->validate([
            'area' => 'sometimes|string|max:100|unique:areas,area,' . $area->id,
            'fee'  => 'sometimes|integer|min:0',
        ]);

        $area->update($data);
        return response()->json($area);
    }

    public function destroy(Area $area)
    {
        $area->delete();
        return response()->json(null, 204);
    }
}
