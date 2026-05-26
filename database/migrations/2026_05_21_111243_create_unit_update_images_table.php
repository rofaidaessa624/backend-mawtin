<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unit_update_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_update_id')->constrained()->onDelete('cascade');
            $table->string('path');
                        $table->string('file_type')->nullable(); // ✅ أضف هذا السطر

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_update_images');
    }
};