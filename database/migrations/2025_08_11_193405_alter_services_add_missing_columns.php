<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            // add columns only if missing (no doctrine/dbal required)
            if (! Schema::hasColumn('services', 'code')) {
                $table->string('code', 100)->nullable()->after('slug');
            }
            if (! Schema::hasColumn('services', 'name')) {
                $table->string('name')->nullable()->after('code');
            }
            if (! Schema::hasColumn('services', 'description')) {
                $table->text('description')->nullable()->after('name');
            }
            if (! Schema::hasColumn('services', 'meta')) {
                $table->json('meta')->nullable()->after('description');
            }
            if (! Schema::hasColumn('services', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('meta');
            }
            if (! Schema::hasColumn('services', 'default_flow_template_id')) {
                $table->unsignedBigInteger('default_flow_template_id')->nullable()->after('entry_flow_template_id');
                $table->foreign('default_flow_template_id')
                    ->references('id')
                    ->on('flow_templates')
                    ->nullOnDelete();
            }
            if (! Schema::hasColumn('services', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // backfill data to avoid nulls breaking seeders/queries
        // 1) code <= slug (if slug exists)
        if (Schema::hasColumn('services', 'code') && Schema::hasColumn('services', 'slug')) {
            DB::statement('UPDATE services SET code = slug WHERE code IS NULL AND slug IS NOT NULL');
            // unique index for code (avoid NOT NULL to skip doctrine/dbal; we already populated)
            try {
                DB::statement('CREATE UNIQUE INDEX services_code_unique ON services (code)');
            } catch (\Throwable $e) {
                // ignore if already exists
            }
        }

        // 2) name <= name_en if available
        if (Schema::hasColumn('services', 'name') && Schema::hasColumn('services', 'name_en')) {
            DB::statement('UPDATE services SET name = name_en WHERE name IS NULL AND name_en IS NOT NULL');
        }

        // 3) default_flow_template_id <= entry_flow_template_id if present
        if (
            Schema::hasColumn('services', 'default_flow_template_id') &&
            Schema::hasColumn('services', 'entry_flow_template_id')
        ) {
            DB::statement('UPDATE services SET default_flow_template_id = entry_flow_template_id WHERE default_flow_template_id IS NULL AND entry_flow_template_id IS NOT NULL');
        }
    }

    public function down(): void
    {
        // drop indexes/foreigns safely
        if (Schema::hasColumn('services', 'default_flow_template_id')) {
            try {
                Schema::table('services', function (Blueprint $table) {
                    $table->dropForeign(['default_flow_template_id']);
                });
            } catch (\Throwable $e) {
                // ignore
            }
        }
        try {
            DB::statement('DROP INDEX services_code_unique ON services');
        } catch (\Throwable $e) {
            // ignore
        }

        Schema::table('services', function (Blueprint $table) {
            if (Schema::hasColumn('services', 'code')) {
                $table->dropColumn('code');
            }
            if (Schema::hasColumn('services', 'name')) {
                $table->dropColumn('name');
            }
            if (Schema::hasColumn('services', 'description')) {
                $table->dropColumn('description');
            }
            if (Schema::hasColumn('services', 'meta')) {
                $table->dropColumn('meta');
            }
            if (Schema::hasColumn('services', 'is_active')) {
                $table->dropColumn('is_active');
            }
            if (Schema::hasColumn('services', 'default_flow_template_id')) {
                $table->dropColumn('default_flow_template_id');
            }
            if (Schema::hasColumn('services', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
