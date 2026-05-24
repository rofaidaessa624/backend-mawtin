<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('units', function (Blueprint $table) {
            // $table->string('project_name')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('units', function (Blueprint $table) {
            // $table->string('project_name')->nullable(false)->change();
        });
    }
};