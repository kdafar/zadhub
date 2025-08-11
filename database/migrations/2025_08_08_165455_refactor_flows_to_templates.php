<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Tear down in reverse‐dependency order
        Schema::dropIfExists('whatsapp_sessions');
        Schema::dropIfExists('flows');
        Schema::dropIfExists('flow_versions');
        Schema::dropIfExists('flow_templates');

        // 2) Create flow_templates (no FK on live_version_id yet)
        Schema::create('flow_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_active')->default(true);

            // column only; FK comes later
            $table->unsignedBigInteger('live_version_id')->nullable();

            $table->timestamps();
        });

        // 3) Create flow_versions (with changelog)
        Schema::create('flow_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flow_template_id')
                ->constrained('flow_templates')
                ->cascadeOnDelete();
            $table->unsignedInteger('version_number')->default(1);
            $table->longText('json_definition')->nullable();
            $table->longText('builder_data');
            // ← add the missing changelog column here:
            $table->text('changelog')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        // 4) Now that flow_versions exists, hook up flow_templates.live_version_id
        Schema::table('flow_templates', function (Blueprint $table) {
            $table->foreign('live_version_id')
                ->references('id')
                ->on('flow_versions')
                ->onDelete('SET NULL');
        });

        // 5) Create flows
        Schema::create('flows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('flow_template_id')
                ->constrained('flow_templates')
                ->cascadeOnDelete();
            $table->foreignId('live_version_id')
                ->constrained('flow_versions')
                ->cascadeOnDelete();
            $table->string('trigger_keyword')->unique();
            $table->timestamps();
        });

        // 6) Create whatsapp_sessions
        Schema::create('whatsapp_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('customer_phone_number')->index();
            $table->foreignId('provider_id')
                ->constrained()
                ->cascadeOnDelete();

            // define column, then add FK
            $table->unsignedBigInteger('current_flow_id')->nullable();
            $table->foreign('current_flow_id')
                ->references('id')
                ->on('flows')
                ->onDelete('SET NULL');

            $table->string('current_step_uuid')->nullable();
            $table->string('status')->default('active');
            $table->json('data')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // Drop in reverse order
        Schema::dropIfExists('whatsapp_sessions');
        Schema::dropIfExists('flows');

        // remove FK on flow_templates
        Schema::table('flow_templates', function (Blueprint $table) {
            $table->dropForeign(['live_version_id']);
        });

        Schema::dropIfExists('flow_versions');
        Schema::dropIfExists('flow_templates');
    }
};
