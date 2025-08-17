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
        Schema::table('flow_versions', function (Blueprint $t) {
            $t->unsignedBigInteger('flow_id')->nullable()->after('id');
            $t->index('flow_id');
            // optional FK (add only if flows table exists now)
            $t->foreign('flow_id')->references('id')->on('flows')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('flow_versions', function (Blueprint $t) {
            if (Schema::hasColumn('flow_versions', 'flow_id')) {
                $t->dropForeign(['flow_id']);
                $t->dropColumn('flow_id');
            }
        });
    }
};
