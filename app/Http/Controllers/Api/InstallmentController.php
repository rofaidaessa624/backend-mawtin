<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Installment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class InstallmentController extends Controller
{
    // جلب الأقساط
    public function index()
    {
        $installments = Installment::with(['client', 'unit'])->get();
        
        return response()->json([
            'success' => true,
            'data' => $installments
        ]);
    }

    // إضافة قسط واحد
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_id' => 'required|exists:clients,id',
            'unit_id' => 'required|exists:units,id',
            'installment_number' => 'required|integer|min:1',
            'amount' => 'required|numeric|min:0',
            'due_date' => 'required|date',
            'status' => 'required|in:pending,paid,overdue,cancelled',
            'paid_amount' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $installment = Installment::create([
                'client_id' => $request->client_id,
                'unit_id' => $request->unit_id,
                'installment_number' => $request->installment_number,
                'amount' => $request->amount,
                'due_date' => $request->due_date,
                'status' => $request->status,
                'paid_amount' => $request->paid_amount ?? 0,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم إضافة القسط بنجاح',
                'data' => $installment
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage()
            ], 500);
        }
    }

    // إضافة أقساط متعددة دفعة واحدة
    public function storeMultiple(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_id' => 'required|exists:clients,id',
            'unit_id' => 'required|exists:units,id',
            'installments' => 'required|array|min:1',
            'installments.*.installment_number' => 'required|integer|min:1',
            'installments.*.amount' => 'required|numeric|min:0',
            'installments.*.due_date' => 'required|date',
            'installments.*.status' => 'nullable|in:pending,paid,overdue,cancelled',
            'installments.*.paid_amount' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            $createdInstallments = [];

            foreach ($request->installments as $inst) {
                $installment = Installment::create([
                    'client_id' => $request->client_id,
                    'unit_id' => $request->unit_id,
                    'installment_number' => $inst['installment_number'],
                    'amount' => $inst['amount'],
                    'due_date' => $inst['due_date'],
                    'status' => $inst['status'] ?? 'pending',
                    'paid_amount' => $inst['paid_amount'] ?? 0,
                ]);

                $createdInstallments[] = $installment;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم إضافة الأقساط بنجاح',
                'data' => $createdInstallments,
                'count' => count($createdInstallments)
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage()
            ], 500);
        }
    }

    // جلب قسط معين
    public function show($id)
    {
        $installment = Installment::with(['client', 'unit'])->find($id);

        if (!$installment) {
            return response()->json([
                'success' => false,
                'message' => 'القسط غير موجود'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $installment
        ]);
    }

    // تحديث قسط
    public function update(Request $request, $id)
    {
        $installment = Installment::find($id);

        if (!$installment) {
            return response()->json([
                'success' => false,
                'message' => 'القسط غير موجود'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'amount' => 'sometimes|numeric|min:0',
            'due_date' => 'sometimes|date',
            'status' => 'sometimes|in:pending,paid,overdue,cancelled',
            'paid_amount' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $installment->update($request->only(['amount', 'due_date', 'status', 'paid_amount']));

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث القسط بنجاح',
            'data' => $installment
        ]);
    }

    // حذف قسط
    public function destroy($id)
    {
        $installment = Installment::find($id);

        if (!$installment) {
            return response()->json([
                'success' => false,
                'message' => 'القسط غير موجود'
            ], 404);
        }

        $installment->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف القسط بنجاح'
        ]);
    }

    // جلب أقساط عميل معين
    public function getClientInstallments($clientId)
    {
        try {
            $installments = Installment::where('client_id', $clientId)
                ->with(['unit'])
                ->orderBy('due_date', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $installments,
                'count' => count($installments)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage()
            ], 500);
        }
    }

    // جلب ملخص الأقساط لعميل
    public function getClientInstallmentsSummary($clientId)
    {
        try {
            $installments = Installment::where('client_id', $clientId)->get();

            $totalAmount = $installments->sum('amount');
            $paidAmount = $installments->sum('paid_amount');
            $remainingAmount = $totalAmount - $paidAmount;
            $pendingCount = $installments->where('status', 'pending')->count();
            $paidCount = $installments->where('status', 'paid')->count();
            $overdueCount = $installments->where('status', 'overdue')->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'total_installments' => count($installments),
                    'total_amount' => $totalAmount,
                    'paid_amount' => $paidAmount,
                    'remaining_amount' => $remainingAmount,
                    'pending_count' => $pendingCount,
                    'paid_count' => $paidCount,
                    'overdue_count' => $overdueCount,
                    'percentage_paid' => $totalAmount > 0 ? round(($paidAmount / $totalAmount) * 100, 2) : 0
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage()
            ], 500);
        }
    }
}
