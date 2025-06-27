<?php

namespace App\Models;

use App\Enums\DiscountType;
use App\Enums\PaymentStatus;
use App\Models\PurchaseItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Purchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'supplier_id',
        'invoice_number',
        'total_amount',
        'paid_amount',
        'payment_status',
        'purchase_date',
        'notes',
        'invoice_discount_type',
        'invoice_discount_value',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'purchase_date' => 'date',
        'invoice_discount_value' => 'decimal:2',
        'invoice_discount_type' => DiscountType::class,
        'payment_status' => PaymentStatus::class,
    ];

    protected $appends = [
        'remaining_balance',
        'total_cost_after_discount',
        'supplier_name',
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    // Get the remaining balance by subtracting paid amount from total amount.
    public function getRemainingBalanceAttribute()
    {
        return (float) ($this->total_amount - $this->paid_amount);
    }

    // Get the total cost after applying the invoice discount
    public function getTotalCostAfterDiscountAttribute()
    {
        $netCost = (float) $this->items->sum(function ($item) {
            return $item->total_cost_for_item;
        });

        if ($this->invoice_discount_type === DiscountType::Fixed) {
            $netCost -= (float) $this->invoice_discount_value;
        } elseif ($this->invoice_discount_type === DiscountType::Percentage) {
            $netCost -= ($netCost * ((float) $this->invoice_discount_value / 100));
        }

        return (float) max(0, $netCost);
    }

    // Get the supplier's full name or a default message if not specified
    public function getSupplierNameAttribute()
    {
        return $this->supplier ? $this->supplier->full_name : __('supplier.not_specified');
    }
}
