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
            if (! Schema::hasColumn('flow_versions', 'definition')) {
                // JSON works on MySQL 5.7+/MariaDB 10.2.7+. If older, switch to longText.
                $table->json('definition')->nullable()->after('id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('flow_versions', function (Blueprint $table) {
            if (Schema::hasColumn('flow_versions', 'definition')) {
                $table->dropColumn('definition');
            }
        });
    }
};
