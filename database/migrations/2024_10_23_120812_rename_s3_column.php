<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sync', function (Blueprint $table) {
            $table->renameColumn('S3_download_path', 'user_storage_download_path');
        });
    }

    public function down(): void
    {
        Schema::table('sync', function (Blueprint $table) {
            $table->renameColumn('user_storage_download_path', 'S3_download_path');
        });
    }
};
