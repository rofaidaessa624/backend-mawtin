<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UnitUpdate;
use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\Unit;
use App\Models\Installment;
use App\Models\Notification;


class UserDashboardController extends Controller
{
    public function dashboard(Request $request)
    {
        $client = $request->user();
        
        $unit = $client->units()->first();
        
        if (!$unit) {
            return response()->json([
                'message' => 'لا توجد وحدة مرتبطة بهذا العميل'
            ], 404);
        }

        $installments = $client->installments()
            ->where('unit_id', $unit->id)
            ->orderBy('due_date')
            ->get();

        // ✅ جلب التطورات مع الصور
        $unit_updates = UnitUpdate::where('unit_id', $unit->id)
            ->with('images') // ← ده المهم
            ->orderBy('created_at', 'desc')
            ->get();

        // ✅ إضافة image_url لكل صورة عشان الفرونت يقراها
        $unit_updates->transform(function ($update) {
            if ($update->images && $update->images->count() > 0) {
                $update->images->transform(function ($image) {
                    $image->image_url = url('storage/' . $image->path);
                    return $image;
                });
            } else {
                $update->images = []; // نضمن إنها مصفوفة فاضية مش null
            }
            return $update;
        });

        return response()->json([
            'client' => $client,
            'unit' => $unit,
            'installments' => $installments,
            'installments_summary' => [
                'total_installments' => $installments->count(),
                'paid_count' => $installments->where('status', 'paid')->count(),
                'pending_count' => $installments->where('status', 'pending')->count(),
                'overdue_count' => $installments->where('status', 'overdue')->count(),
                'total_paid' => $installments->sum('paid_amount'),
                'total_remaining' => $installments->where('status', '!=', 'paid')->sum('amount')
            ],
            'unit_updates' => $unit_updates,
            'notifications' => []
        ]);
    }

    public function stats(Request $request)
    {
        if ($request->user()->role === 'admin') {
            $totalClients = Client::count();
            $totalUnits = Unit::count();
            $totalInstallments = Installment::count();
            $paidInstallments = Installment::where('status', 'paid')->count();
            $pendingInstallments = Installment::where('status', 'pending')->count();
            $totalPaid = Installment::sum('paid_amount');
            $totalRemaining = Installment::where('status', 'pending')->sum('amount');

            return response()->json([
                'success' => true,
                'data' => [
                    'total_clients' => $totalClients,
                    'total_units' => $totalUnits,
                    'total_installments' => $totalInstallments,
                    'paid_installments' => $paidInstallments,
                    'pending_installments' => $pendingInstallments,
                    'total_paid' => $totalPaid,
                    'total_remaining' => $totalRemaining,
                ]
            ]);
        }

        $client = $request->user();
        $installments = $client->installments;
$notifications = Notification::where('client_id', $client->id)
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get();


        return response()->json([
            'success' => true,
            'data' => [
                'total_installments' => $installments->count(),
                'paid_installments' => $installments->where('status', 'paid')->count(),
                'pending_installments' => $installments->where('status', 'pending')->count(),
                'total_paid' => $installments->sum('paid_amount'),
                'total_remaining' => $installments->where('status', 'pending')->sum('amount'),
                'notifications' => $notifications,

                ]
        ]);
    }
}