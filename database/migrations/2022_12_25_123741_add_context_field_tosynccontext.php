<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('sync_logs', function (Blueprint $table) {
            $table->json('context')->nullable();
        });
    }

    public function down()
    {
        Schema::table('sync_logs', function (Blueprint $table) {
            $table->dropColumn(['context']);
        });
    }
};
