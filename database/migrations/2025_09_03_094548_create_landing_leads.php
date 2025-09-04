<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landing_leads', function (Blueprint $t) {
            $t->id();
            $t->string('name')->nullable();
            $t->string('company')->nullable();
            $t->string('email')->nullable();
            $t->string('phone');
            $t->string('use_case')->nullable(); // restaurant|pharmacy|grocery|logistics|other
            $t->string('locale', 5)->default('en');
            $t->string('message', 500)->nullable();
            $t->json('utm')->nullable();
            $t->ipAddress('ip')->nullable();
            $t->timestamps();
            $t->index(['created_at', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_leads');
    }
};
