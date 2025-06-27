<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $orders = Order::with(['orderItems', 'payments'])->get();
        $customers_count = Customer::count();

        $low_stock_products = Product::where('stock', '<', 10)->get();

        $bestSellingProductsIds = DB::table('products')
            ->select('products.id', DB::raw('SUM(order_items.quantity) AS total_sold'))
            ->join('order_items', 'order_items.product_id', '=', 'products.id')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->groupBy('products.id')
            ->havingRaw('SUM(order_items.quantity) > 10')
            ->get();

        $bestSellingProducts = Product::with('image')
            ->whereIn('id', $bestSellingProductsIds->pluck('id'))
            ->get()
            ->map(function ($product) use ($bestSellingProductsIds) {
                $product->total_sold = $bestSellingProductsIds->firstWhere('id', $product->id)->total_sold;
                return $product;
            });

        $currentMonthBestSellingIds = DB::table('products')
            ->select('products.id', DB::raw('SUM(order_items.quantity) AS total_sold'))
            ->join('order_items', 'order_items.product_id', '=', 'products.id')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereYear('orders.created_at', date('Y'))
            ->whereMonth('orders.created_at', date('m'))
            ->groupBy('products.id')
            ->havingRaw('SUM(order_items.quantity) > 500')
            ->get();

        $currentMonthBestSelling = Product::with('image')
            ->whereIn('id', $currentMonthBestSellingIds->pluck('id'))
            ->get()
            ->map(function ($product) use ($currentMonthBestSellingIds) {
                $product->total_sold = $currentMonthBestSellingIds->firstWhere('id', $product->id)->total_sold;
                return $product;
            });

        $pastSixMonthsHotProductsIds = DB::table('products')
            ->select('products.id', DB::raw('SUM(order_items.quantity) AS total_sold'))
            ->join('order_items', 'order_items.product_id', '=', 'products.id')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.created_at', '>=', now()->subMonths(6))
            ->groupBy('products.id')
            ->havingRaw('SUM(order_items.quantity) > 1000')
            ->get();

        $pastSixMonthsHotProducts = Product::with('image')
            ->whereIn('id', $pastSixMonthsHotProductsIds->pluck('id'))
            ->get()
            ->map(function ($product) use ($pastSixMonthsHotProductsIds) {
                $product->total_sold = $pastSixMonthsHotProductsIds->firstWhere('id', $product->id)->total_sold;
                return $product;
            });


        return view('home', [
            'orders_count' => $orders->count(),
            'income' => $orders->map(function ($i) {
                return $i->received_amount > $i->order_total ? $i->order_total : $i->received_amount;
            })->sum(),
            'income_today' => $orders->where('created_at', '>=', date('Y-m-d') . ' 00:00:00')->map(function ($i) {
                return $i->received_amount > $i->order_total ? $i->order_total : $i->received_amount;
            })->sum(),
            'customers_count' => $customers_count,
            'low_stock_products' => $low_stock_products,
            'best_selling_products' => $bestSellingProducts,
            'current_month_products' => $currentMonthBestSelling,
            'past_months_products' => $pastSixMonthsHotProducts,
        ]);
    }
}
