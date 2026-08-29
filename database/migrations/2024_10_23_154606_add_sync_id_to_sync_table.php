<?php

use App\Models\Sync;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sync', function (Blueprint $table) {
            $table->renameColumn('user_storage_download_path', 'sync_id');
        });
        Sync::where('sync_id', 'like', 'userZips%')->each(function (Sync $sync) {
            // remove "userZips" from the start of the string:
            $sync->sync_id = str_replace('userZips/', '', $sync->sync_id);
            $sync->sync_id = str_replace('.zip', '', $sync->sync_id);
            $sync->save();
        });
    }
};
