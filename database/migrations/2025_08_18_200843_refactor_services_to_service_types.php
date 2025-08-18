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
        Schema::table('providers', function (Blueprint $table) {
            $table->dropForeign(['service_id']);
            $table->renameColumn('service_id', 'service_type_id');
        });

        Schema::table('flow_templates', function (Blueprint $table) {
            $table->dropForeign(['service_id']);
            $table->renameColumn('service_id', 'service_type_id');
        });

        Schema::table('service_keywords', function (Blueprint $table) {
            $table->dropForeign(['service_id']);
            $table->renameColumn('service_id', 'service_type_id');
        });

        Schema::table('flow_triggers', function (Blueprint $table) {
            $table->dropForeign(['service_id']);
            $table->renameColumn('service_id', 'service_type_id');
        });

        Schema::table('flow_versions', function (Blueprint $table) {
            $table->dropForeign(['service_id']);
            $table->renameColumn('service_id', 'service_type_id');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['service_id']);
            $table->renameColumn('service_id', 'service_type_id');
        });

        Schema::table('adapter_api_logs', function (Blueprint $table) {
            $table->dropForeign(['service_id']);
            $table->renameColumn('service_id', 'service_type_id');
        });

        Schema::table('analytics_events', function (Blueprint $table) {
            $table->dropForeign(['service_id']);
            $table->renameColumn('service_id', 'service_type_id');
        });

        Schema::table('whatsapp_sessions', function (Blueprint $table) {
            $table->dropForeign(['service_id']);
            $table->renameColumn('service_id', 'service_type_id');
        });

        Schema::rename('services', 'service_types');

        Schema::table('providers', function (Blueprint $table) {
            $table->foreign('service_type_id')->references('id')->on('service_types')->cascadeOnDelete();
        });

        Schema::table('flow_templates', function (Blueprint $table) {
            $table->foreign('service_type_id')->references('id')->on('service_types')->cascadeOnDelete();
        });

        Schema::table('service_keywords', function (Blueprint $table) {
            $table->foreign('service_type_id')->references('id')->on('service_types')->cascadeOnDelete();
        });

        Schema::table('flow_triggers', function (Blueprint $table) {
            $table->foreign('service_type_id')->references('id')->on('service_types')->nullOnDelete();
        });

        Schema::table('flow_versions', function (Blueprint $table) {
            $table->foreign('service_type_id')->references('id')->on('service_types')->nullOnDelete();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreign('service_type_id')->references('id')->on('service_types')->cascadeOnDelete();
        });

        Schema::table('adapter_api_logs', function (Blueprint $table) {
            $table->foreign('service_type_id')->references('id')->on('service_types')->nullOnDelete();
        });

        Schema::table('analytics_events', function (Blueprint $table) {
            $table->foreign('service_type_id')->references('id')->on('service_types')->nullOnDelete();
        });

        Schema::table('whatsapp_sessions', function (Blueprint $table) {
            $table->foreign('service_type_id')->references('id')->on('service_types')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('providers', function (Blueprint $table) {
            $table->dropForeign(['service_type_id']);
            $table->renameColumn('service_type_id', 'service_id');
        });

        Schema::table('flow_templates', function (Blueprint $table) {
            $table->dropForeign(['service_type_id']);
            $table->renameColumn('service_type_id', 'service_id');
        });

        Schema::table('service_keywords', function (Blueprint $table) {
            $table->dropForeign(['service_type_id']);
            $table->renameColumn('service_type_id', 'service_id');
        });

        Schema::table('flow_triggers', function (Blueprint $table) {
            $table->dropForeign(['service_type_id']);
            $table->renameColumn('service_type_id', 'service_id');
        });

        Schema::table('flow_versions', function (Blueprint $table) {
            $table->dropForeign(['service_type_id']);
            $table->renameColumn('service_type_id', 'service_id');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['service_type_id']);
            $table->renameColumn('service_type_id', 'service_id');
        });

        Schema::table('adapter_api_logs', function (Blueprint $table) {
            $table->dropForeign(['service_type_id']);
            $table->renameColumn('service_type_id', 'service_id');
        });

        Schema::table('analytics_events', function (Blueprint $table) {
            $table->dropForeign(['service_type_id']);
            $table->renameColumn('service_type_id', 'service_id');
        });

        Schema::table('whatsapp_sessions', function (Blueprint $table) {
            $table->dropForeign(['service_type_id']);
            $table->renameColumn('service_type_id', 'service_id');
        });

        Schema::rename('service_types', 'services');

        Schema::table('providers', function (Blueprint $table) {
            $table->foreign('service_id')->references('id')->on('services')->cascadeOnDelete();
        });

        Schema::table('flow_templates', function (Blueprint $table) {
            $table->foreign('service_id')->references('id')->on('services')->cascadeOnDelete();
        });

        Schema::table('service_keywords', function (Blueprint $table) {
            $table->foreign('service_id')->references('id')->on('services')->cascadeOnDelete();
        });

        Schema::table('flow_triggers', function (Blueprint $table) {
            $table->foreign('service_id')->references('id')->on('services')->nullOnDelete();
        });

        Schema::table('flow_versions', function (Blueprint $table) {
            $table->foreign('service_id')->references('id')->on('services')->nullOnDelete();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreign('service_id')->references('id')->on('services')->cascadeOnDelete();
        });

        Schema::table('adapter_api_logs', function (Blueprint $table) {
            $table->foreign('service_id')->references('id')->on('services')->nullOnDelete();
        });

        Schema::table('analytics_events', function (Blueprint $table) {
            $table->foreign('service_id')->references('id')->on('services')->nullOnDelete();
        });

        Schema::table('whatsapp_sessions', function (Blueprint $table) {
            $table->foreign('service_id')->references('id')->on('services')->nullOnDelete();
        });
    }
};