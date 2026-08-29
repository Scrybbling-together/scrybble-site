<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('gumroad_licenses', static function (Blueprint $table) {
            $table->id();

            $table->string('license');
            $table->boolean('valid')->default(false);
            $table->timestamp('last_validated_at')->nullable();
            $table->foreignIdFor(User::class);

            $table->timestamps();
        });
    }
};
