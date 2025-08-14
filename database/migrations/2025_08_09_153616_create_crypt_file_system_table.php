<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('crypt_file_system', function (Blueprint $table) {
            $table->id();

            $table->foreignIdFor(User::class);
            $table->string("encryption_key_salt", 256)->comment("The salt used to derive the encryption key for CryptFS.")->nullable();
            $table->boolean("key_needs_derivation")->comment("A flag specifying whether during next login, the key needs to be derived and stored in the database.");

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crypt_file_system');
    }
};
