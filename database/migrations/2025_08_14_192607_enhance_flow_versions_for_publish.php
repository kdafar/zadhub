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
            if (! Schema::hasColumn('flow_versions', 'status')) {
                $table->string('status', 20)->default('draft')->after('id'); // draft|published|archived
            }
            if (! Schema::hasColumn('flow_versions', 'published_at')) {
                $table->timestamp('published_at')->nullable()->after('status');
            }
            if (! Schema::hasColumn('flow_versions', 'version')) {
                $table->unsignedInteger('version')->default(1)->after('published_at');
            }
            if (! Schema::hasColumn('flow_versions', 'service_id')) {
                $table->unsignedBigInteger('service_id')->nullable()->after('version');
                $table->foreign('service_id')->references('id')->on('services')->nullOnDelete();
            }
            if (! Schema::hasColumn('flow_versions', 'provider_id')) {
                $table->unsignedBigInteger('provider_id')->nullable()->after('service_id');
                $table->foreign('provider_id')->references('id')->on('providers')->nullOnDelete();
            }
            if (! Schema::hasColumn('flow_versions', 'name')) {
                $table->string('name', 120)->nullable()->after('provider_id');
            }

            $table->index(['status', 'published_at'], 'fv_status_pub_idx');
            $table->index(['service_id', 'provider_id', 'status', 'published_at'], 'fv_sp_status_pub_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('flow_versions', function (Blueprint $table) {
            $table->dropIndex('fv_status_pub_idx');
            $table->dropIndex('fv_sp_status_pub_idx');

            if (Schema::hasColumn('flow_versions', 'provider_id')) {
                $table->dropConstrainedForeignId('provider_id');
            }
            if (Schema::hasColumn('flow_versions', 'service_id')) {
                $table->dropConstrainedForeignId('service_id');
            }

            $table->dropColumn(['status', 'published_at', 'version', 'service_id', 'provider_id', 'name']);
        });
    }
};
