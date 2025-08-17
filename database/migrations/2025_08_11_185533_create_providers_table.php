<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('providers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug'); // unique per service
            $table->enum('status', ['active', 'paused', 'disabled'])->default('active');
            $table->string('api_base_url')->nullable();
            $table->enum('auth_type', ['bearer', 'apikey', 'none'])->default('none');
            $table->boolean('is_sandbox')->default(false);
            $table->json('locale_defaults')->nullable();
            $table->json('feature_flags')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['service_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('providers');
    }
};
