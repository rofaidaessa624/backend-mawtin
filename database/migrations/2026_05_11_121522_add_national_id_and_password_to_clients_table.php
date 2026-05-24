<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('clients', function (Blueprint $table) {
            if (!Schema::hasColumn('clients', 'national_id')) {
                $table->string('national_id')->unique()->after('phone');
            }
            if (!Schema::hasColumn('clients', 'password')) {
                $table->string('password')->after('national_id');
            }
        });
    }

    public function down()
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['national_id', 'password']);
        });
    }
};