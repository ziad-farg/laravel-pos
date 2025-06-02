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
        Schema::table('user_cart', function (Blueprint $table) {
            $table->decimal('discount_percentage', 5, 1)->default(0.0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_cart', function (Blueprint $table) {
            $table->dropColumn('discount_percentage');
        });
    }
};
