<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained()->cascadeOnDelete();
            $table->string('key_name'); // e.g. api_key, bearer_token
            $table->text('secret_encrypted');
            $table->json('meta')->nullable(); // scopes, expiry, rotation
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_credentials');
    }
};
