<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedBigInteger('till_id')->nullable()->after('user_id');
        });

        $defaultTillId = DB::table('tills')->min('id');

        DB::table('payments')->whereNull('till_id')->update(['till_id' => $defaultTillId]);

        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedBigInteger('till_id')->nullable(false)->change();
            $table->foreign('till_id')->references('id')->on('tills')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('till_id');
        });
    }
};
