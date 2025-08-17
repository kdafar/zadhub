<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flow_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flow_template_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->boolean('is_stable')->default(false);
            $table->json('schema_json');      // nodes, edges, screens
            $table->json('components_json')->nullable(); // reusable components definition
            $table->timestamps();

            $table->unique(['flow_template_id', 'version']);
        });

        // flow_templates.latest_version_id FK
        Schema::table('flow_templates', function (Blueprint $table) {
            $table->foreign('latest_version_id')->references('id')->on('flow_versions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('flow_templates', function (Blueprint $table) {
            $table->dropConstrainedForeignId('latest_version_id');
        });
        Schema::dropIfExists('flow_versions');
    }
};
