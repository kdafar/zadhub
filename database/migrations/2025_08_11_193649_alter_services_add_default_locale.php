<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            if (! Schema::hasColumn('services', 'default_locale')) {
                // ISO language code like 'en', 'ar', etc.
                $table->string('default_locale', 5)->default('en')->after('description');
                $table->index('default_locale');
            }
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            if (Schema::hasColumn('services', 'default_locale')) {
                $table->dropIndex(['default_locale']);
                $table->dropColumn('default_locale');
            }
        });
    }
};
