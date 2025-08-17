<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('session_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('whatsapp_sessions')->cascadeOnDelete();
            $table->string('screen')->index();
            $table->json('state_json');
            $table->timestamps();

            $table->unique(['session_id', 'screen']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_states');
    }
};
