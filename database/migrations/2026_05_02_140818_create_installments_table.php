<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
     Schema::create('installments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
    $table->foreignId('unit_id')->constrained('units')->onDelete('cascade');
    $table->integer('installment_number');
    $table->decimal('amount', 15, 2);
    $table->date('due_date');
    $table->enum('status', ['pending', 'paid', 'overdue', 'cancelled'])->default('pending');
    $table->decimal('paid_amount', 15, 2)->default(0);
    $table->timestamps();
});
    }

    public function down()
    {
        Schema::dropIfExists('installments');
    }
};