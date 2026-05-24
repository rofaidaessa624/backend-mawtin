<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_unit', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->foreignId('unit_id')->constrained('units')->onDelete('cascade');
            $table->decimal('agreed_price', 15, 2);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->date('purchase_date');
            $table->enum('contract_status', ['pending', 'active', 'completed', 'cancelled'])->default('active');
            $table->timestamps();
            $table->unique(['client_id', 'unit_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_unit');
    }
};