<?php

use App\Enums\DiscountType;
use App\Enums\PaymentStatus;
use App\Models\Payment;
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
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->onDelete('set null');
            $table->string('invoice_number')->unique()->nullable();
            $table->decimal('total_amount', 10, 2);
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->string('payment_status')->default(PaymentStatus::Pending->value)
                ->comment('Payment status: ' . implode(', ', PaymentStatus::values()));
            $table->date('purchase_date');
            $table->text('notes')->nullable();
            $table->string('invoice_discount_type')->nullable();
            $table->decimal('invoice_discount_value', 8, 2)->default(0.00);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
