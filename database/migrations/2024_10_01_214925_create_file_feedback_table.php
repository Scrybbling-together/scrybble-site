<?php

use App\Models\Sync;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('remarkable_document_share', function (Blueprint $table) {
            $table->id();

            $table->foreignIdFor(User::class);
            $table->foreignIdFor(Sync::class);
            $table->text('feedback')->nullable();
            $table->boolean('developer_access_consent_granted')->default(false);
            $table->boolean('open_access_consent_granted')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remarkable_document_share');
    }
};
