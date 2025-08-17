<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flow_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->string('slug'); // unique within service
            $table->string('name');
            $table->string('description')->nullable();
            $table->unsignedBigInteger('latest_version_id')->nullable();
            $table->timestamps();

            $table->unique(['service_id', 'slug']);
        });

        // add FK from services.entry_flow_template_id → flow_templates.id
        Schema::table('services', function (Blueprint $table) {
            $table->foreign('entry_flow_template_id')->references('id')->on('flow_templates')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropConstrainedForeignId('entry_flow_template_id');
        });
        Schema::dropIfExists('flow_templates');
    }
};
