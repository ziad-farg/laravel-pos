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
        Schema::create('user_cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_cart_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->integer('quantity')->default(1)->comment('Quantity of the product in the cart');
            $table->decimal('price_at_add', 8, 2)->default(0.00)->comment('Price of the product at the time it was added to the cart');
            $table->string('discount_type')->nullable()->comment('Type of discount applied to the product, e.g., percentage, fixed');
            $table->decimal('discount_value', 8, 2)->default(0.00)->comment('Value of the discount applied to the product');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_cart_items');
    }
};
