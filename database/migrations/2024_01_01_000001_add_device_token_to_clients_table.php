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
        Schema::table('clients', function (Blueprint $table) {
            // Check if column doesn't exist
            if (!Schema::hasColumn('clients', 'device_token')) {
                $table->text('device_token')->nullable()->after('email');
                $table->index('device_token');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            if (Schema::hasColumn('clients', 'device_token')) {
                $table->dropColumn('device_token');
            }
        });
    }
};
