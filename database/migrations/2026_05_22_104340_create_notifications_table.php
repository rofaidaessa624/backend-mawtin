<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
    Schema::create('notifications', function (Blueprint $table) {
    $table->id();

    $table->foreignId('client_id')->constrained()->onDelete('cascade');

    $table->foreignId('unit_id')
        ->nullable()
        ->constrained()
        ->nullOnDelete();

    $table->string('title');

    $table->text('message');

    $table->string('type')->default('info');

    // unit_update / installment
    $table->string('notification_type')->nullable();

    $table->boolean('is_read')->default(false);

    $table->timestamps();
});
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};