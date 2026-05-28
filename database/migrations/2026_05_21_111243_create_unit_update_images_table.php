<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFileTypeToUnitUpdateImagesTable extends Migration
{
    public function up()
    {
        Schema::table('unit_update_images', function (Blueprint $table) {
            $table->string('file_type')->nullable()->after('path');
        });
    }

    public function down()
    {
        Schema::table('unit_update_images', function (Blueprint $table) {
            $table->dropColumn('file_type');
        });
    }
}