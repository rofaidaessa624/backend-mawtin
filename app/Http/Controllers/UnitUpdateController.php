<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UnitUpdate;
use Illuminate\Http\Request;

class UnitUpdateController extends Controller
{
    public function index($unitId)
    {
        $updates = UnitUpdate::where('unit_id', $unitId)
                    ->with('images')
                    ->get();

        return response()->json($updates);
    }

public function store(Request $request)
{
    $request->validate([
        'unit_id'     => 'required|exists:units,id',
        'update_text' => 'nullable|string|max:1000',
        'images'      => 'nullable|array',
        'images.*'    => 'image|mimes:jpeg,png,jpg,gif,webp|max:5120',
    ]);

    $update = UnitUpdate::create([
        'unit_id'     => $request->unit_id,
        'update_text' => $request->update_text ?? '',
    ]);

    if ($request->hasFile('images')) {
        foreach ($request->file('images') as $image) {
            $path = $image->store('unit_updates', 'public');
            $update->images()->create(['path' => $path]);
        }
    }

    // ✅ إرسال إشعار للعملاء المرتبطين بالوحدة
    $unit = \App\Models\Unit::find($request->unit_id);
    if ($unit) {
        $clients = $unit->clients;
        foreach ($clients as $client) {
            \App\Models\Notification::create([
                'client_id' => $client->id,
                'title' => 'تحديث جديد لوحدتك',
                'message' => "مرحلة جديدة: {$request->update_text} - للوحدة {$unit->unit_number}",
                'type' => 'success',
                'is_read' => false,
            ]);
        }
    }

    $update->load('images');
    if ($update->images && $update->images->count() > 0) {
        $update->images->transform(function ($image) {
            $image->image_url = url('storage/' . $image->path);
            return $image;
        });
    }

    return response()->json([
        'success' => true,
        'message' => 'تم إضافة المرحلة بنجاح',
        'data'    => $update
    ], 201);
}
}