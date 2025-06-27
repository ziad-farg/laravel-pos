<?php

namespace App\Models;

use App\Enums\SaleReturnType;
use Illuminate\Database\Eloquent\Model;

class SaleReturn extends Model
{
    protected $fillable = [
        'order_id',
        'user_id',
        'type',
        'return_date',
        'total_refund_amount',
        'notes',
    ];

    protected $casts = [
        'return_date' => 'date',
        'total_refund_amount' => 'decimal:2',
        'type' => SaleReturnType::class,
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function saleReturnItems()
    {
        return $this->hasMany(SaleReturnItem::class);
    }
}
