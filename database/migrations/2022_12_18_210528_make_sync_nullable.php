<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('sync', function (Blueprint $table) {
            $table->string('S3_download_path')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('sync', function (Blueprint $table) {
            $table->string('S3_download_path')->nullable(false)->change();
        });
    }
};
