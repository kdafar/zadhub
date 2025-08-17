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
        Schema::create('meta_flows', function (Blueprint $t) {
            $t->id();
            $t->foreignId('flow_version_id')->constrained('flow_versions')->cascadeOnDelete();
            $t->string('meta_flow_id')->nullable();         // {FLOW_ID} from Meta
            $t->string('status')->default('draft');         // draft|published
            $t->string('template_name')->nullable();        // optional, if you attach to template
            $t->timestamp('published_at')->nullable();
            $t->json('last_payload')->nullable();
            $t->timestamps();
            $t->unique('flow_version_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meta_flows');
    }
};
