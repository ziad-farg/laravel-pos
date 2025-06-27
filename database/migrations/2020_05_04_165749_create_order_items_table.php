<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->decimal('price', 8, 2)->default(0);
            $table->integer('quantity')->default(1);
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('discount_type')->nullable()->comment('Type of discount applied to the item, e.g., percentage, fixed');
            $table->decimal('discount_value', 8, 2)->default(0)->comment('Value of the discount applied to the item');
            $table->integer('quantity_returned')->default(0)->comment('Number of items returned for this order item');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('order_items');
    }
};
