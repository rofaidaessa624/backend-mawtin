<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UnitUpdate;
use Illuminate\Http\Request;

class UnitUpdateController extends Controller
{
    // ✅ تعديل دالة index لتوحيد الهيكل
    public function index($unitId)
    {
        $updates = UnitUpdate::where('unit_id', $unitId)
                    ->with('images')
                    ->orderBy('created_at', 'desc')
                    ->get();
        
        // تحويل مسارات الصور إلى URLs كاملة
        $updates->transform(function ($update) {
            $update->images->transform(function ($image) {
                $image->image_url = url('storage/' . $image->path);
                $image->file_type = $this->getMimeType($image->path);
                return $image;
            });
            return $update;
        });
        
        // ✅ إرجاع نفس هيكل store
        return response()->json([
            'success' => true,
            'data' => $updates
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'unit_id'     => 'required|exists:units,id',
            'update_text' => 'nullable|string|max:1000',
            'images'      => 'nullable|array',
            'images.*'    => 'file|mimes:jpeg,png,jpg,gif,webp,mp4,mov,avi|max:20480', // دعم الفيديو
        ]);

        $update = UnitUpdate::create([
            'unit_id'     => $request->unit_id,
            'update_text' => $request->update_text ?? '',
        ]);

        // ✅ حفظ الملفات (صور وفيديوهات)
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                // تحديد المجلد حسب نوع الملف
                $folder = $file->getMimeType() === 'video/mp4' ? 'unit_videos' : 'unit_updates';
                $path = $file->store($folder, 'public');
                
                $update->images()->create([
                    'path' => $path,
                    'file_type' => $file->getMimeType()
                ]);
            }
        }
        
        // ✅ تحميل العلاقات بعد الحفظ
        $update->load('images');
        
        // إضافة URLs للصور
        $update->images->transform(function ($image) {
            $image->image_url = url('storage/' . $image->path);
            $image->file_type = $image->file_type ?? $this->getMimeType($image->path);
            return $image;
        });

        // إرسال الإشعارات (الكود الموجود عندك)
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

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة المرحلة بنجاح',
            'data'    => $update
        ], 201);
    }
    
    // ✅ إضافة دالة لتحديد نوع الملف
    private function getMimeType($path)
    {
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'mp4' => 'video/mp4',
            'mov' => 'video/quicktime',
            'avi' => 'video/x-msvideo'
        ];
        
        return $mimeTypes[strtolower($extension)] ?? 'application/octet-stream';
    }
    
    // ✅ إضافة دالة لحذف التحديث مع صوره
    public function destroy($id)
    {
        $update = UnitUpdate::find($id);
        
        if (!$update) {
            return response()->json([
                'success' => false,
                'message' => 'التحديث غير موجود'
            ], 404);
        }
        
        // حذف الملفات من التخزين
        foreach ($update->images as $image) {
            $filePath = storage_path('app/public/' . $image->path);
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            $image->delete();
        }
        
        $update->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'تم حذف التحديث بنجاح'
        ]);
    }
    
    // ✅ إضافة دالة لجلب صور تحديث معين (اختياري)
    public function getUpdateFiles($updateId)
    {
        $update = UnitUpdate::with('images')->find($updateId);
        
        if (!$update) {
            return response()->json([
                'success' => false,
                'message' => 'التحديث غير موجود'
            ], 404);
        }
        
        $files = $update->images->map(function($image) {
            return [
                'id' => $image->id,
                'url' => url('storage/' . $image->path),
                'path' => $image->path,
                'file_type' => $image->file_type ?? $this->getMimeType($image->path),
                'image_url' => url('storage/' . $image->path)
            ];
        });
        
        return response()->json([
            'success' => true,
            'data' => $files
        ]);
    }
}