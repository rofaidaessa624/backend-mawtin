<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up(): void
{
    Schema::create('units', function (Blueprint $table) {
        $table->id();
        $table->string('unit_number'); // رقم الوحدة (مثل A-101)
        // $table->string('project_name'); // اسم المشروع
        $table->string('unit_type'); // نوع الوحدة (apartment, villa, etc)
        $table->decimal('total_price', 15, 2); // السعر الإجمالي
        $table->decimal('down_payment', 15, 2); // المقدم
        $table->integer('number_of_installments'); // عدد الأقساط
        $table->string('location'); // الموقع
        $table->integer('area'); // المساحة
        $table->integer('bedrooms')->default(0); // عدد الغرف
        $table->integer('bathrooms')->default(0); // عدد الحمامات
        $table->string('status')->default('available'); // الحالة (sold, available, etc)
        $table->text('description')->nullable(); // الوصف
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('units');
}

};
