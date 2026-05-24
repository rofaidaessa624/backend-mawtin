<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Unit;          // ✅ أضف هذا
use App\Models\Client;        // ✅ أضف هذا (للتأكد)
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // 1. إنشاء المستخدمين
        User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('12345678'),
                'phone' => '01000000000',
                'role' => 'admin',
                'is_active' => true
            ]
        );
        
        User::updateOrCreate(
            ['email' => 'staff@gmail.com'],
            [
                'name' => 'Staff User',
                'password' => Hash::make('12345678'),
                'phone' => '01111111111',
                'role' => 'staff',
                'is_active' => true
            ]
        );
        
        // 2. إنشاء وحدات تجريبية إذا لم توجد
        // if (Unit::count() == 0) {
        //     Unit::create([
        //         'unit_number' => 'A101',
        //         'project_name' => 'النخلة',
        //         'unit_type' => 'apartment',
        //         'total_price' => 850000,
        //         'down_payment' => 150000,
        //         'number_of_installments' => 12,
        //         'location' => 'مدينة نصر',
        //         'area' => 120,
        //         'bedrooms' => 3,
        //         'bathrooms' => 2,
        //         'status' => 'available'
        //     ]);
        // }

        // 3. يمكنك إضافة عملاء تجريبيين هنا (اختياري)
        // إذا أردت إضافة عميل تلقائياً، يمكنك استخدام:
        // Client::create([...])
    }
}