<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Rank;
use Illuminate\Http\Request;

class RankController extends Controller
{
    public function index()
    {
        return Rank::orderBy('order')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'            => 'required|string|max:50',
            'designation_fee' => 'required|integer|min:0',
            'color'           => 'required|string|max:50',
            'order'           => 'required|integer|min:0',
        ]);

        return response()->json(Rank::create($data), 201);
    }

    public function update(Request $request, Rank $rank)
    {
        $data = $request->validate([
            'name'            => 'sometimes|string|max:50',
            'designation_fee' => 'sometimes|integer|min:0',
            'color'           => 'sometimes|string|max:50',
            'order'           => 'sometimes|integer|min:0',
        ]);

        $rank->update($data);
        return response()->json($rank);
    }

    public function destroy(Rank $rank)
    {
        $rank->delete();
        return response()->json(null, 204);
    }
}
