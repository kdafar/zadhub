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
        Schema::table('flow_triggers', function (Blueprint $table) {
            if (! Schema::hasColumn('flow_triggers', 'use_latest_published')) {
                $table->boolean('use_latest_published')->default(false)->after('flow_version_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('flow_triggers', function (Blueprint $table) {
            $table->dropColumn('use_latest_published');
        });
    }
};
