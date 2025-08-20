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
        Schema::table('flow_versions', function (Blueprint $table) {
            if (! Schema::hasColumn('flow_versions', 'meta')) {
                // JSON on MySQL/Postgres; sqlite stores as TEXT, Laravel casts handle it
                $table->json('meta')->nullable()->after('use_latest_published');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('flow_versions', function (Blueprint $table) {
            if (Schema::hasColumn('flow_versions', 'meta')) {
                $table->dropColumn('meta');
            }
        });
    }
};
