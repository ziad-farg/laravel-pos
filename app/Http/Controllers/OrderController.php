<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\OrderStoreRequest;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = new Order();
        if ($request->start_date) {
            $orders = $orders->where('created_at', '>=', $request->start_date);
        }
        if ($request->end_date) {
            $orders = $orders->where('created_at', '<=', $request->end_date . ' 23:59:59');
        }
        $orders = $orders->with(['orderItems.product', 'payments', 'customer'])->latest()->paginate(10);

        $total = $orders->map(function ($i) {
            return $i->total();
        })->sum();
        $receivedAmount = $orders->map(function ($i) {
            return $i->receivedAmount();
        })->sum();

        return view('orders.index', compact('orders', 'total', 'receivedAmount'));
    }

    public function store(OrderStoreRequest $request)
    {
        DB::beginTransaction();
        try {
            $order = Order::create([
                'customer_id' => $request->customer_id,
                'user_id' => $request->user()->id,
                'discount_type' => $request->discount_type,
                'discount_value' => $request->discount_value,
            ]);

            $cart = $request->user()->cart()->get();

            foreach ($cart as $item) {
                $originalPricePerUnit = $item->price;
                $quantity = $item->pivot->quantity;
                $discountPercentage = $item->pivot->discount_percentage ?? 0;

                $totalOriginalPriceForItem = $originalPricePerUnit * $quantity;
                $priceAfterItemDiscountPerUnit = $originalPricePerUnit * (1 - ($discountPercentage / 100));
                $totalPriceForItemAfterItemDiscount = $priceAfterItemDiscountPerUnit * $quantity;

                $order->orderItems()->create([
                    'price' => $totalOriginalPriceForItem,
                    'quantity' => $quantity,
                    'product_id' => $item->id,
                    'discount_percentage' => $discountPercentage,
                    'price_after_discount' => $totalPriceForItemAfterItemDiscount,
                ]);

                $product = Product::find($item->id);
                if ($product) {
                    $product->quantity -= $quantity;
                    $product->save();
                }
            }

            $request->user()->cart()->detach();

            $order->payments()->create([
                'amount' => $request->amount,
                'user_id' => $request->user()->id,
                'till_id' => $request->till_id,
            ]);

            DB::commit();

            return response()->json(['message' => 'Order created successfully!', 'order_id' => $order->id], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create order. Please try again.', 'error' => $e->getMessage()], 500);
        }
    }

    public function partialPayment(Request $request)
    {
        $orderId = $request->order_id;
        $amount = $request->amount;

        $order = Order::findOrFail($orderId);

        $remainingAmount = $order->total() - $order->receivedAmount();
        if ($amount > $remainingAmount) {
            return redirect()->route('orders.index')->withErrors('Amount exceeds remaining balance');
        }

        DB::transaction(function () use ($order, $amount, $request) {
            $order->payments()->create([
                'amount' => $amount,
                'user_id' => $request->user()->id,
                'till_id' => $request->till_id,
            ]);
        });

        return redirect()->route('orders.index')->with('success', 'Partial payment of ' . config('settings.currency_symbol') . number_format($amount, 2) . ' made successfully.');
    }
}
