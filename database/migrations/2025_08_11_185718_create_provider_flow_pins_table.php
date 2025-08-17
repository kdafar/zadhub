<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_flow_pins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained()->cascadeOnDelete();
            $table->foreignId('flow_template_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pinned_version_id')->constrained('flow_versions')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['provider_id', 'flow_template_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_flow_pins');
    }
};
