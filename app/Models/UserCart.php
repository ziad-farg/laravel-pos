<?php

namespace App\Models;

use App\Enums\DiscountType;
use Illuminate\Database\Eloquent\Model;

class UserCart extends Model
{
    protected $fillable = [
        'user_id',
        'customer_id',
        'invoice_discount_type',
        'invoice_discount_value',
    ];

    protected $casts = [
        'invoice_discount_value' => 'decimal:2',
        'invoice_discount_type' => DiscountType::class,
    ];

    protected $appends = [
        'total_items_count',
        'total_quantity',
        'sub_total',
        'total_after_item_discounts',
        'total_after_cart_discount',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(UserCartItem::class);
    }

    // Accessors for calculated Unique Item Types
    public function getTotalItemsCountAttribute()
    {
        return $this->items()->count();
    }

    // Accessors for calculated Total Quantity in Cart
    public function getTotalQuantityAttribute()
    {
        return $this->items->sum('quantity');
    }

    // Accessors for calculated Subtotal Price before Discounts
    public function getSubTotalAttribute()
    {
        $subtotal = $this->items->sum(function ($item) {
            return (float) $item->price_at_add * (int) $item->quantity;
        });

        return round($subtotal, 2);
    }

    // Accessors for calculated Total Price after Item Discounts
    public function getTotalAfterItemDiscountsAttribute()
    {
        return round($this->items->sum('total_price_for_item'), 2);
    }

    // Accessors for calculated Total Price after Cart Discount
    public function getTotalAfterCartDiscountAttribute()
    {
        $netTotal = $this->total_after_item_discounts;

        if ($this->invoice_discount_type && $this->invoice_discount_type === DiscountType::Fixed) {
            $netTotal -= $this->invoice_discount_value;
        } elseif ($this->invoice_discount_type && $this->invoice_discount_type === DiscountType::Percentage) {
            $netTotal -= ($this->total_after_item_discounts * ($this->invoice_discount_value / 100));
        }

        return max(0, round($netTotal, 2));
    }
}
