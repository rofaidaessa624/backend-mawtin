<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use App\Models\UnitUpdate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class UnitImageController extends Controller
{
    public function upload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'unit_id' => 'required|exists:units,id',
            'images' => 'required|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'captions' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $uploadedImages = [];
            
            foreach ($request->file('images') as $index => $image) {
                // حفظ الصورة
                $path = $image->store('unit-images', 'public');
                $fullPath = Storage::url($path);
                
                $caption = $request->captions[$index] ?? null;
                
                $uploadedImages[] = [
                    'url' => $fullPath,
                    'caption' => $caption,
                    'order' => $index
                ];
                
                // إضافة كتحديث للوحدة
                UnitUpdate::create([
                    'unit_id' => $request->unit_id,
                    'update_text' => $caption ?? 'تم إضافة صورة جديدة للوحدة',
                    'images' => [$fullPath],
                    'update_type' => 'image',
                    'notify_clients' => true,
                ]);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'تم رفع الصور بنجاح',
                'data' => [
                    'uploaded' => count($uploadedImages),
                    'images' => $uploadedImages
                ]
            ], 201);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في رفع الصور: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // جلب صور الوحدة
    public function getUnitImages($unitId)
    {
        $unit = Unit::find($unitId);
        
        if (!$unit) {
            return response()->json([
                'success' => false,
                'message' => 'الوحدة غير موجودة'
            ], 404);
        }
        
        $images = UnitUpdate::where('unit_id', $unitId)
            ->where(function($query) {
                $query->where('update_type', 'image')
                      ->orWhereNotNull('images');
            })
            ->orderBy('created_at', 'desc')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $images
        ]);
    }
}