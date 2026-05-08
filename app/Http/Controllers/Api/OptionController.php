<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Option;
use Illuminate\Http\Request;

class OptionController extends Controller
{
    public function index()
    {
        return Option::orderBy('name')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'price'       => 'required|integer|min:0',
            'description' => 'nullable|string|max:255',
        ]);

        return response()->json(Option::create($data), 201);
    }

    public function update(Request $request, Option $option)
    {
        $data = $request->validate([
            'name'        => 'sometimes|string|max:100',
            'price'       => 'sometimes|integer|min:0',
            'description' => 'nullable|string|max:255',
        ]);

        $option->update($data);
        return response()->json($option);
    }

    public function destroy(Option $option)
    {
        $option->delete();
        return response()->json(null, 204);
    }
}
