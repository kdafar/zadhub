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
            if (! Schema::hasColumn('flow_versions', 'is_template')) {
                $table->boolean('is_template')->default(false)->after('flow_template_id');
            }
            if (! Schema::hasColumn('flow_versions', 'use_latest_published')) {
                $table->boolean('use_latest_published')->default(false)->after('is_template');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('flow_versions', function (Blueprint $table) {
            if (Schema::hasColumn('flow_versions', 'is_template')) {
                $table->dropColumn('is_template');
            }
            if (Schema::hasColumn('flow_versions', 'use_latest_published')) {
                $table->dropColumn('use_latest_published');
            }
        });
    }
};
