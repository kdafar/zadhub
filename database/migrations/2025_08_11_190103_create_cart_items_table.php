<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained('carts')->cascadeOnDelete();
            $table->string('item_ref'); // e.g., item_123 / plan_4G_20GB / doctor_77_slot_...
            $table->string('title');
            $table->decimal('price', 10, 3)->default(0);
            $table->unsignedInteger('qty')->default(1);
            $table->json('variations')->nullable();
            $table->json('provider_payload')->nullable();
            $table->timestamps();

            $table->index(['cart_id', 'item_ref']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
