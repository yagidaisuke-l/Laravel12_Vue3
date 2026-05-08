<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Codeword;
use Illuminate\Http\Request;

class CodewordController extends Controller
{
    public function index()
    {
        return Codeword::orderBy('site_name')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'site_name'      => 'required|string|max:100',
            'word'           => 'required|string|max:100',
            'discount_type'  => 'required|in:fixed,percent',
            'discount_value' => 'required|integer|min:0',
            'description'    => 'nullable|string|max:255',
            'is_active'      => 'boolean',
        ]);

        return response()->json(Codeword::create($data), 201);
    }

    public function update(Request $request, Codeword $codeword)
    {
        $data = $request->validate([
            'site_name'      => 'sometimes|string|max:100',
            'word'           => 'sometimes|string|max:100',
            'discount_type'  => 'sometimes|in:fixed,percent',
            'discount_value' => 'sometimes|integer|min:0',
            'description'    => 'nullable|string|max:255',
            'is_active'      => 'boolean',
        ]);

        $codeword->update($data);
        return response()->json($codeword);
    }

    public function destroy(Codeword $codeword)
    {
        $codeword->delete();
        return response()->json(null, 204);
    }
}
