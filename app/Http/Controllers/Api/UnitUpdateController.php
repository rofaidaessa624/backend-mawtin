<?php

namespace App\Http\Controllers\Api;
use App\Models\Notification;
use App\Services\FirebaseNotificationService;

use App\Http\Controllers\Controller;
use App\Models\UnitUpdate;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UnitUpdateController extends Controller
{
    public function index($unitId)
    {
        $unit = Unit::find($unitId);

        if (!$unit) {
            return response()->json([
                'success' => false,
                'message' => 'الوحدة غير موجودة'
            ], 404);
        }

        $updates = UnitUpdate::where('unit_id', $unitId)
                    ->with('images')
                    ->orderBy('created_at', 'desc')
                    ->get();

        $updates->transform(function ($update) {
            if ($update->images) {
                $update->images->transform(function ($image) {
                    $image->image_url = url('storage/' . $image->path);
                    return $image;
                });
            }
            return $update;
        });

        return response()->json([
            'success' => true,
            'data' => $updates
        ], 200);
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

        $update->load('images');

        if ($update->images) {
            $update->images->transform(function ($image) {
                $image->image_url = url('storage/' . $image->path);
                return $image;
            });
        }
$unit = Unit::with('client')->find($request->unit_id);

if ($unit && $unit->client) {

    // حفظ إشعار داخل النظام
    Notification::create([
        'client_id' => $unit->client->id,
        'unit_id' => $unit->id,
        'title' => 'تحديث جديد على وحدتك',
        'message' => $request->update_text ?: 'تم إضافة تحديث جديد على وحدتك',
        'type' => 'info',
        'notification_type' => 'unit_update',
    ]);

    // Chrome Notification
    app(FirebaseNotificationService::class)->sendToClient(
        $unit->client,
        'تحديث جديد على وحدتك',
        $request->update_text ?: 'تم إضافة تحديث جديد على وحدتك'
    );
}


        return response()->json([
            'success' => true,
            'message' => 'تم إضافة المرحلة بنجاح',
            'data'    => $update
        ], 201);
    }

    public function destroy($id)
    {
        $update = UnitUpdate::findOrFail($id);

        if ($update->images) {
            foreach ($update->images as $image) {
                Storage::disk('public')->delete($image->path);
            }
        }

        $update->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف المرحلة بنجاح'
        ], 200);
    }
}