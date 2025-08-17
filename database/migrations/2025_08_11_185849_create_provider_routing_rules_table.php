<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_routing_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained()->cascadeOnDelete();
            $table->enum('rule_type', ['last_used', 'nearest', 'fixed', 'custom'])->default('fixed');
            $table->json('rule_config')->nullable(); // e.g., fixed_provider_id, geo rules
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_routing_rules');
    }
};
