<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
// use Maatwebsite\Excel\Facades\Excel;
// use App\Exports\ClientsExport;
// use App\Imports\ClientsImport;


class ClientController extends Controller
{
    public function index()
    {
        $clients = Client::with(['units', 'installments'])->get();
        return response()->json(['success' => true, 'data' => $clients]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string|max:255',
            'phone'     => 'required|string',
            'phone2'    => 'required|string',
            'national_id' => 'required|string|unique:clients,national_id',
            'password'  => 'required|string|min:6',
            'address'   => 'nullable|string',
            'gender'    => 'nullable|in:male,female',
            'broker_name'  => 'required|string',
            'broker_phone' => 'required|string',
            'unit_id'   => 'nullable|exists:units,id',
            'number_of_installments' => 'nullable|integer|min:1|max:60',
            'down_payment_percentage' => 'nullable|integer|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'بيانات غير صحيحة', 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $client = Client::create([
                'full_name' => $request->full_name,
                'phone'     => $request->phone,
                'phone2'    => $request->phone2,
                'national_id'=> $request->national_id,
                'password'  => Hash::make($request->password),
                'address'   => $request->address,
                'gender'    => $request->gender,
                'broker_name'  => $request->broker_name,
                'broker_phone' => $request->broker_phone,
                'is_active' => true,
                'user_id'   => auth()->id(),
            ]);

            // إذا تم إرسال unit_id مباشرة (تكامل مع الطريقة القديمة)
            if ($request->filled('unit_id')) {
                $unit = Unit::find($request->unit_id);
                if ($unit) {
                    $downPaymentPercentage = $request->down_payment_percentage ?? 20;
                    $downPaymentAmount = $unit->total_price * ($downPaymentPercentage / 100);
                    
                    DB::table('client_unit')->insert([
                        'client_id' => $client->id,
                        'unit_id'   => $unit->id,
                        'agreed_price' => $unit->total_price,
                        'paid_amount'  => $downPaymentAmount,
                        'purchase_date' => now()->toDateString(),
                        'contract_status' => 'active',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    
                    $unit->update(['status' => 'sold']);
                    
                    if ($request->filled('number_of_installments') && $request->number_of_installments > 0) {
                        $remainingAmount = $unit->total_price - $downPaymentAmount;
                        $installmentAmount = $remainingAmount / $request->number_of_installments;
                        for ($i = 1; $i <= $request->number_of_installments; $i++) {
                            DB::table('installments')->insert([
                                'client_id' => $client->id,
                                'unit_id'   => $unit->id,
                                'installment_number' => $i,
                                'amount'    => round($installmentAmount, 2),
                                'due_date'  => now()->addMonths($i),
                                'status'    => 'pending',
                                'paid_amount' => 0,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }
            }
            
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'تم إضافة العميل بنجاح',
                'data'    => $client->load(['units', 'installments'])
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'حدث خطأ: ' . $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $client = Client::with(['units', 'installments'])->find($id);
        if (!$client) {
            return response()->json(['success' => false, 'message' => 'العميل غير موجود'], 404);
        }
        return response()->json(['success' => true, 'data' => $client]);
    }

    public function update(Request $request, $id)
    {
        $client = Client::find($id);
        if (!$client) {
            return response()->json(['success' => false, 'message' => 'العميل غير موجود'], 404);
        }
        
        $validator = Validator::make($request->all(), [
            'full_name' => 'sometimes|string|max:255',
            'phone'     => 'sometimes|string',
            'phone2'    => 'nullable|string',
            'address'   => 'nullable|string',
            'gender'    => 'nullable|in:male,female',
            'broker_name'  => 'nullable|string',
            'broker_phone' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }
        
        $data = $request->except(['password', 'national_id']);
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }
        $client->update($data);
        
        return response()->json(['success' => true, 'message' => 'تم تحديث العميل بنجاح', 'data' => $client]);
    }

    public function destroy($id)
    {
        $client = Client::find($id);
        if (!$client) {
            return response()->json(['success' => false, 'message' => 'العميل غير موجود'], 404);
        }
        $client->delete();
        return response()->json(['success' => true, 'message' => 'تم حذف العميل بنجاح']);
    }

    // ✅ دالة واحدة لجلب وحدات العميل (بدون تكرار)
    public function getClientUnits($clientId)
    {
        $client = Client::findOrFail($clientId);
        $units = $client->units()->withPivot('agreed_price', 'paid_amount', 'purchase_date', 'contract_status')->get();
        return response()->json(['success' => true, 'data' => $units]);
    }

    // ربط العميل بوحدة (endpoint منفصل)
    public function linkToUnit(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'unit_id'   => 'required|exists:units,id',
            'agreed_price' => 'required|numeric',
            'paid_amount'   => 'nullable|numeric',
            'purchase_date' => 'nullable|date',
            'contract_status' => 'nullable|string'
        ]);

        $client = Client::findOrFail($request->client_id);
        $client->units()->attach($request->unit_id, [
            'agreed_price' => $request->agreed_price,
            'paid_amount'  => $request->paid_amount,
            'purchase_date' => $request->purchase_date ?? now(),
            'contract_status' => $request->contract_status ?? 'active'
        ]);

        return response()->json(['success' => true, 'message' => 'Linked successfully']);
    }

// تصدير العملاء إلى CSV
public function exportCsv()
{
    $clients = Client::with(['units', 'installments'])->get();

    $headers = [
        'Content-Type' => 'text/csv',
        'Content-Disposition' => 'attachment; filename="clients.csv"',
    ];

    $callback = function() use ($clients) {
        $file = fopen('php://output', 'w');
        // إضافة BOM لدعم العربية
        fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // رؤوس الأعمدة
        fputcsv($file, [
            'ID', 'الاسم الكامل', 'الهاتف', 'الهاتف 2', 'الرقم القومي',
            'العنوان', 'النوع', 'اسم السمسار', 'رقم السمسار',
            'عدد الوحدات', 'عدد الأقساط', 'الحالة', 'تاريخ الإنشاء'
        ]);

        foreach ($clients as $client) {
            fputcsv($file, [
                $client->id,
                $client->full_name,
                $client->phone,
                $client->phone2 ?? '',
                $client->national_id ?? '',
                $client->address ?? '',
                $client->gender ?? '',
                $client->broker_name ?? '',
                $client->broker_phone ?? '',
                $client->units->count(),
                $client->installments->count(),
                $client->is_active ? 'نشط' : 'غير نشط',
                $client->created_at->format('Y-m-d'),
            ]);
        }
        fclose($file);
    };

    return response()->stream($callback, 200, $headers);
}

// استيراد العملاء من CSV
public function importCsv(Request $request)
{
    $request->validate([
        'file' => 'required|file|mimes:csv,txt'
    ]);

    $file = $request->file('file');
    $handle = fopen($file->getRealPath(), 'r');
    
    // تخطي أول سطر (الرؤوس)
    $headers = fgetcsv($handle);
    
    $imported = 0;
    $errors = [];

    while (($row = fgetcsv($handle)) !== false) {
        // تأكد من وجود الحد الأدنى من البيانات
        if (count($row) < 3) {
            $errors[] = 'صف ناقص: ' . json_encode($row);
            continue;
        }
        
        // الأعمدة حسب الترتيب: 0:id,1:full_name,2:phone,3:phone2,...
        $full_name = trim($row[1] ?? '');
        $phone     = trim($row[2] ?? '');
        $phone2    = trim($row[3] ?? '');
        $national_id = trim($row[4] ?? '');
        $address   = trim($row[5] ?? '');
        $gender    = trim($row[6] ?? '');
        $broker_name  = trim($row[7] ?? '');
        $broker_phone = trim($row[8] ?? '');

        if (empty($full_name) || empty($phone)) {
            $errors[] = 'الاسم أو الهاتف فارغ في السطر: ' . json_encode($row);
            continue;
        }

        // تجنب تكرار الهاتف
        if (Client::where('phone', $phone)->exists()) {
            $errors[] = "رقم الهاتف $phone موجود مسبقاً - تم تخطي السطر";
            continue;
        }

        Client::create([
            'full_name'    => $full_name,
            'phone'        => $phone,
            'phone2'       => $phone2 ?: null,
            'national_id'  => $national_id ?: null,
            'address'      => $address ?: null,
            'gender'       => $gender ?: null,
            'broker_name'  => $broker_name ?: null,
            'broker_phone' => $broker_phone ?: null,
            'password'     => bcrypt('123456'),
            'is_active'    => true,
        ]);

        $imported++;
    }

    fclose($handle);

    return response()->json([
        'success' => true,
        'message' => "تم استيراد $imported عميل بنجاح",
        'errors'  => $errors
    ]);
}

}