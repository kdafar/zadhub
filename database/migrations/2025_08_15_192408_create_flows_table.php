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
        Schema::create('flows', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('provider_id');
            $t->string('name');
            $t->string('trigger_keyword');
            $t->boolean('is_active')->default(true);
            $t->json('meta')->nullable();
            $t->timestamps();
            $t->softDeletes();

            $t->foreign('provider_id')->references('id')->on('providers')->cascadeOnDelete();
            $t->unique(['provider_id', 'trigger_keyword']); // one trigger per provider
            $t->index(['provider_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flows');
    }
};
