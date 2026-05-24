<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('unit_updates', function (Blueprint $table) {
            if (!Schema::hasColumn('unit_updates', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down()
    {
        Schema::table('unit_updates', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};