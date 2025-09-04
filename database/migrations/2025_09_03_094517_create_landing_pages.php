<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landing_pages', function (Blueprint $t) {
            $t->id();
            $t->string('slug'); // e.g. 'whatsapp-bot'
            $t->string('locale', 5); // 'en'|'ar'
            $t->string('title');
            $t->string('meta_title')->nullable();
            $t->text('meta_description')->nullable();
            $t->json('sections');
            $t->boolean('is_published')->default(false);
            $t->timestamp('published_at')->nullable();
            $t->unsignedInteger('version')->default(1);
            $t->timestamps();
            $t->unique(['slug', 'locale']);
            $t->index(['locale', 'is_published']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_pages');
    }
};
