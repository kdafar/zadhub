<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 32)->index();
            $table->enum('status', ['active', 'completed', 'abandoned'])->default('active');
            $table->string('locale', 5)->default('en');
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('provider_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('flow_version_id')->nullable()->constrained()->nullOnDelete();
            $table->string('current_screen')->nullable();
            $table->uuid('flow_token')->nullable()->index();
            $table->json('context')->nullable(); // address/msisdn/etc.
            $table->timestamp('last_interacted_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_sessions');
    }
};
