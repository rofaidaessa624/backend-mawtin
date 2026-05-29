<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();

            // 👤 Client Info
            $table->string('full_name');
            $table->string('phone');
            $table->string('phone2')->nullable();

            $table->string('national_id')->unique()->nullable();
            $table->string('password')->nullable();

            // 🔔 Firebase Token (IMPORTANT)
            $table->text('device_token')->nullable();

            // 📍 Extra Info
            $table->text('address')->nullable();
            $table->enum('gender', ['male', 'female'])->nullable();

            // 🧑‍💼 Broker Info
            $table->string('broker_name')->nullable();
            $table->string('broker_phone')->nullable();

            // ⚙️ Status
            $table->boolean('is_active')->default(true);

            // 🔗 Relations
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('cascade');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};