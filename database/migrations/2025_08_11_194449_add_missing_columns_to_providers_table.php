<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('providers', function (Blueprint $table) {
            if (! Schema::hasColumn('providers', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('status');
            }
            if (! Schema::hasColumn('providers', 'callback_url')) {
                $table->string('callback_url')->nullable()->after('api_base_url');
            }
            if (! Schema::hasColumn('providers', 'contact_email')) {
                $table->string('contact_email')->nullable()->after('callback_url');
            }
            if (! Schema::hasColumn('providers', 'contact_phone')) {
                $table->string('contact_phone', 32)->nullable()->after('contact_email');
            }
            if (! Schema::hasColumn('providers', 'timezone')) {
                $table->string('timezone', 64)->default('UTC')->after('contact_phone');
            }
            if (! Schema::hasColumn('providers', 'meta')) {
                $table->json('meta')->nullable()->after('feature_flags');
            }
        });
    }

    public function down(): void
    {
        Schema::table('providers', function (Blueprint $table) {
            if (Schema::hasColumn('providers', 'meta')) {
                $table->dropColumn('meta');
            }
            if (Schema::hasColumn('providers', 'timezone')) {
                $table->dropColumn('timezone');
            }
            if (Schema::hasColumn('providers', 'contact_phone')) {
                $table->dropColumn('contact_phone');
            }
            if (Schema::hasColumn('providers', 'contact_email')) {
                $table->dropColumn('contact_email');
            }
            if (Schema::hasColumn('providers', 'callback_url')) {
                $table->dropColumn('callback_url');
            }
            if (Schema::hasColumn('providers', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });
    }
};
