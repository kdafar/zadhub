<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_rate_limits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('window_sec')->default(60);
            $table->unsignedInteger('max_calls')->default(60);
            $table->unsignedInteger('current_count')->default(0);
            $table->timestamp('reset_at')->nullable();
            $table->timestamps();

            $table->unique(['provider_id', 'window_sec']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_rate_limits');
    }
};
