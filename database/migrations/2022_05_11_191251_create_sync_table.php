<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync', static function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class);
            $table->string('filename');
            $table->boolean('completed')
                ->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropColumns('sync', ['user_id']);
        Schema::dropIfExists('sync');
    }
};
