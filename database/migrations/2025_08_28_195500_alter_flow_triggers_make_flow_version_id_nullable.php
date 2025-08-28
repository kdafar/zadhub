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
            $table->unsignedBigInteger('flow_version_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('flow_triggers', function (Blueprint $table) {
            // Making a nullable column non-nullable on rollback can fail if it contains nulls.
            // A default value or a data migration would be needed for a production environment.
            $table->unsignedBigInteger('flow_version_id')->nullable(false)->change();
        });
    }
};
