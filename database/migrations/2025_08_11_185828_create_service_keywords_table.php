<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_keywords', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 5)->default('en'); // en|ar|*
            $table->string('keyword');
            $table->unsignedInteger('priority')->default(100);
            $table->timestamps();

            $table->unique(['service_id', 'locale', 'keyword']);
            $table->index(['service_id', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_keywords');
    }
};
