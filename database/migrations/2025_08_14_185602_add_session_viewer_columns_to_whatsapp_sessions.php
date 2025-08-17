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
        Schema::table('whatsapp_sessions', function (Blueprint $table) {
            if (! Schema::hasColumn('whatsapp_sessions', 'flow_history')) {
                $table->json('flow_history')->nullable()->after('context'); // chronological events
            }
            if (! Schema::hasColumn('whatsapp_sessions', 'ended_at')) {
                $table->timestamp('ended_at')->nullable()->after('last_interacted_at');
            }
            if (! Schema::hasColumn('whatsapp_sessions', 'ended_reason')) {
                $table->string('ended_reason', 120)->nullable()->after('ended_at');
            }
            if (! Schema::hasColumn('whatsapp_sessions', 'last_message_type')) {
                $table->string('last_message_type', 40)->nullable()->after('current_screen');
            }
            if (! Schema::hasColumn('whatsapp_sessions', 'last_payload')) {
                $table->json('last_payload')->nullable()->after('last_message_type');
            }

            // Helpful indexes for the viewer
            $table->index(['status', 'last_interacted_at'], 'ws_status_last_idx');
            $table->index('phone', 'ws_phone_idx');
            $table->index('service_id', 'ws_service_idx');
            $table->index('provider_id', 'ws_provider_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('whatsapp_sessions', function (Blueprint $table) {
            $table->dropIndex('ws_status_last_idx');
            $table->dropIndex('ws_phone_idx');
            $table->dropIndex('ws_service_idx');
            $table->dropIndex('ws_provider_idx');

            $table->dropColumn(['flow_history', 'ended_at', 'ended_reason', 'last_message_type', 'last_payload']);
        });
    }
};
