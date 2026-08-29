<?php

use App\Models\Sync;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('sync_logs', function (Blueprint $table) {
            $table->id();

            $table->string('message');
            $table->string('severity');
            $table->foreignIdFor(Sync::class);

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('sync_logs');
    }
};
