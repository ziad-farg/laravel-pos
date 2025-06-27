<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name',
        'description',
        'barcode',
        'price',
        'stock',
        'status'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'stock' => 'integer',
    ];

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function userCartItems()
    {
        return $this->hasMany(UserCartItem::class);
    }

    public function saleReturnItems()
    {
        return $this->hasMany(SaleReturnItem::class);
    }

    public function purchaseCarts()
    {
        return $this->hasMany(PurchaseCart::class);
    }

    public function image()
    {
        return $this->morphOne(Image::class, 'imageable');
    }
}
