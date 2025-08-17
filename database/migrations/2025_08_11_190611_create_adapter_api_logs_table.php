<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adapter_api_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->string('endpoint'); // GET /v1/menu
            $table->integer('status_code')->nullable();
            $table->unsignedInteger('latency_ms')->default(0);
            $table->string('error_code')->nullable(); // normalized error name
            $table->string('request_id')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['provider_id', 'service_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adapter_api_logs');
    }
};
