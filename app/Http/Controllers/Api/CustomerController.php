<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::query();

        if ($request->filled('phone')) {
            $phone = preg_replace('/[-\s]/', '', $request->phone);
            $query->where('phone', $phone);
        }

        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        return $query->orderByDesc('updated_at')->paginate(30);
    }

    public function show(Customer $customer)
    {
        $customer->loadCount(['reservations as visit_count' => fn ($q) => $q->where('reservation_status', '確定')]);
        $customer->load('ngCasts:id,name');

        $customer->cast_history = $customer->cast_history_attribute;

        return response()->json($customer);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'phone'  => 'required|string|max:20|unique:customers,phone',
            'name'   => 'nullable|string|max:100',
            'status' => 'in:temp,full',
            'memo'   => 'nullable|string',
        ]);

        return response()->json(Customer::create($data), 201);
    }

    public function update(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'name'   => 'nullable|string|max:100',
            'status' => 'in:temp,full',
            'memo'   => 'nullable|string',
        ]);

        $customer->update($data);
        return response()->json($customer);
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();
        return response()->json(null, 204);
    }

    // 顧客のNGキャスト同期
    public function syncNgCasts(Request $request, Customer $customer)
    {
        $request->validate(['cast_ids' => 'array', 'cast_ids.*' => 'exists:casts,id']);
        $customer->ngCasts()->sync($request->cast_ids ?? []);
        return response()->json(['cast_ids' => $customer->ngCasts()->pluck('id')]);
    }
}
