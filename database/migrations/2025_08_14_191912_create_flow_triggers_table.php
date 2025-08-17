<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('flow_triggers', function (Blueprint $table) {
            $table->id();
            $table->string('keyword', 64);                 // e.g., 'restaurant', 'clinic', 'book'
            $table->unsignedBigInteger('service_id')->nullable();
            $table->unsignedBigInteger('provider_id')->nullable();
            $table->unsignedBigInteger('flow_version_id'); // points to the concrete flow to run
            $table->string('locale', 5)->nullable();       // optional default locale (en/ar)
            $table->unsignedInteger('priority')->default(10);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['keyword', 'is_active', 'priority'], 'ft_keyword_active_idx');
            $table->index(['service_id', 'provider_id'], 'ft_service_provider_idx');
            $table->foreign('service_id')->references('id')->on('services')->nullOnDelete();
            $table->foreign('provider_id')->references('id')->on('providers')->nullOnDelete();
            $table->foreign('flow_version_id')->references('id')->on('flow_versions')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flow_triggers');
    }
};
