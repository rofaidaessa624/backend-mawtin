<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Unit; // ✅ لازم دي

class UnitController extends Controller
{
public function index()
{
    $units = Unit::paginate(10);

    return response()->json($units);
}

    public function show($id)
    {
        $unit = Unit::findOrFail($id);
        return response()->json(['success' => true, 'data' => $unit], 200);
    }

public function store(Request $request)
{
       $request->validate([
        'unit_number' => 'required|string|unique:units,unit_number',
        'unit_type'   => 'nullable|string',
        'total_price' => 'required|numeric|min:0',
        'down_payment' => 'nullable|numeric|min:0',
        'number_of_installments' => 'nullable|integer|min:1',
        'location'    => 'required|string',         // ✅ إجباري
        'area'        => 'required|integer|min:1',  // ✅ إجباري
        'bedrooms'    => 'nullable|integer|min:0',
        'bathrooms'   => 'nullable|integer|min:0',
        'status'      => 'nullable|string|in:available,sold,reserved',
        'description' => 'nullable|string',
    ]);

    $unit = Unit::create($request->only([
        'unit_number', 'unit_type', 'total_price', 'down_payment',
        'number_of_installments', 'location', 'area', 'bedrooms',
        'bathrooms', 'status', 'description'
    ]));


    // الصور
    if ($request->hasFile('images')) {
        foreach ($request->file('images') as $image) {
            $path = $image->store('units', 'public');

            $unit->images()->create([
                'path' => $path
            ]);
        }
    }

    return response()->json($unit);
}
    public function update(Request $request, $id)
    {
        $unit = Unit::findOrFail($id);
        $unit->update($request->all());

        return response()->json(['success' => true, 'message' => 'تم التحديث', 'data' => $unit], 200);
    }

    public function destroy($id)
    {
        Unit::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'تم الحذف'], 200);
    }
}