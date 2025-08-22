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
        Schema::table('providers', function (Blueprint $table) {
            if (! Schema::hasColumn('providers', 'whatsapp_phone_number_id')) {
                $table->string('whatsapp_phone_number_id')->nullable()->after('id');
            }
            if (! Schema::hasColumn('providers', 'api_token')) {
                $table->string('api_token')->nullable()->after('whatsapp_phone_number_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('providers', function (Blueprint $table) {
            if (Schema::hasColumn('providers', 'whatsapp_phone_number_id')) {
                $table->dropColumn('whatsapp_phone_number_id');
            }
            if (Schema::hasColumn('providers', 'api_token')) {
                $table->dropColumn('api_token');
            }
        });
    }
};
