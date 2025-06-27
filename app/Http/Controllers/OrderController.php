<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Enums\OrderStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\OrderStoreRequest;


class OrderController extends Controller
{


    /**
     * Display a listing of the orders with optional filtering, searching, and pagination.
     *
     * This method retrieves orders from the database, allowing filtering by search term,
     * date range, customer, user, and status. It also loads related models such as order items,
     * products, payments, customer, and user for each order. The results are paginated.
     *
     * If the request expects a JSON response, it returns the orders along with pagination details.
     * Otherwise, it returns the orders view with total and received amounts calculated for the current page.
     *
     * @param  \Illuminate\Http\Request  $request  The incoming HTTP request containing filter and search parameters.
     * @return \Illuminate\Http\JsonResponse|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        $ordersQuery = Order::query()->with(['orderItems.product', 'payments', 'customer', 'user']);

        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = '%' . $request->search . '%';
            $ordersQuery->where(function ($query) use ($searchTerm) {
                $query->where('id', 'LIKE', $searchTerm)
                    ->orWhere('status', 'LIKE', $searchTerm)
                    ->orWhereHas('customer', function ($q) use ($searchTerm) {
                        $q->where('first_name', 'LIKE', $searchTerm)
                            ->orWhere('last_name', 'LIKE', $searchTerm);
                    })
                    ->orWhereHas('user', function ($q) use ($searchTerm) {
                        $q->where('name', 'LIKE', $searchTerm);
                    });
            });
        }

        if ($request->start_date) {
            $ordersQuery->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->end_date) {
            $ordersQuery->whereDate('created_at', '<=', $request->end_date);
        }

        if ($request->has('customer_id') && !empty($request->customer_id)) {
            $ordersQuery->where('customer_id', $request->customer_id);
        }

        if ($request->has('user_id') && !empty($request->user_id)) {
            $ordersQuery->where('user_id', $request->user_id);
        }

        if ($request->has('status') && !empty($request->status)) {
            $ordersQuery->where('status', $request->status);
        }

        $orders = $ordersQuery->latest()->paginate(10);

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Orders retrieved successfully',
                'data' => $orders->items(),
                'pagination' => [
                    'total' => $orders->total(),
                    'per_page' => $orders->perPage(),
                    'current_page' => $orders->currentPage(),
                    'last_page' => $orders->lastPage(),
                    'from' => $orders->firstItem(),
                    'to' => $orders->lastItem(),
                ],
            ]);
        }

        $total = $orders->sum(function ($order) {
            return $order->order_total;
        });

        $receivedAmount = $orders->sum(function ($order) {
            return $order->received_amount;
        });

        return view('orders.index', compact('orders', 'total', 'receivedAmount'));
    }

    /**
     * Store a new order based on the user's cart.
     *
     * This method processes the user's cart, creates an order, and handles payment if provided.
     * It validates the request using OrderStoreRequest, checks for an authenticated user,
     * retrieves the user's cart and its items, and ensures sufficient stock for each product.
     * If successful, it commits the transaction and returns the created order.
     *
     * @param  \App\Http\Requests\OrderStoreRequest  $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function store(OrderStoreRequest $request)
    {
        try {
            DB::beginTransaction();

            $user = Auth::user();
            if (!$user) {
                DB::rollBack();
                if ($request->expectsJson()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => __('auth.unauthenticated')
                    ], 401);
                }
                return redirect()->back()->with('error', __('auth.unauthenticated'));
            }

            $userCart = $user->userCart;

            if (!$userCart) {
                DB::rollBack();
                if ($request->expectsJson()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => __('order.no_active_cart_found')
                    ], 400);
                }
                return redirect()->back()->with('error', __('order.no_active_cart_found'));
            }

            $cartItems = $userCart->items()->get();

            if ($cartItems->isEmpty()) {
                DB::rollBack();
                if ($request->expectsJson()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => __('order.empty_cart')
                    ], 400);
                }
                return redirect()->back()->with('error', __('order.empty_cart'));
            }

            $order = Order::create([
                'customer_id' => $request->customer_id,
                'user_id' => $user->id,
                'invoice_discount_type' => $userCart->invoice_discount_type,
                'invoice_discount_value' => $userCart->invoice_discount_value,
                'status' => OrderStatus::Completed->value,
                'returned_amount' => 0.0,
            ]);

            foreach ($cartItems as $item) {
                $product = Product::lockForUpdate()->find($item->product_id);

                $quantity = $item->quantity;
                $price = $item->price_after_discount;
                $itemDiscountType = $item->discount_type;
                $itemDiscountValue = $item->discount_value;
                $notes = $item->notes ?? null;

                if (!$product || $product->stock < $quantity) {
                    DB::rollBack();
                    if ($request->expectsJson()) {
                        return response()->json([
                            'status' => 'error',
                            'message' => __('order.product_unavailable', ['product' => $product->name ?? 'Unknown Product'])
                        ], 400);
                    }
                    return redirect()->back()->with('error', __('order.product_unavailable', ['product' => $product->name ?? 'Unknown Product']));
                }

                $order->orderItems()->create([
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'price' => $price,
                    'discount_type' => $itemDiscountType?->value,
                    'discount_value' => $itemDiscountValue,
                    'notes' => $notes,
                ]);

                $product->decrement('stock', $quantity);
            }

            $userCart->update([
                'invoice_discount_type' => null,
                'invoice_discount_value' => 0.0,
            ]);

            $userCart->delete();

            if ($request->has('amount') && $request->amount > 0) {
                $order->payments()->create([
                    'amount' => $request->amount,
                    'user_id' => $user->id,
                    'till_id' => $request->till_id ?? null,
                    'payment_method' => $request->payment_method,
                ]);
            }

            DB::commit();

            if ($request->expectsJson()) {
                $order->load(['orderItems.product', 'payments', 'customer', 'user']);
                return response()->json([
                    'status' => 'success',
                    'message' => __('order.success_creating'),
                    'data' => $order
                ], 201);
            }
            return redirect()->route('orders.index')->with('success', __('order.success_creating'));
        } catch (\Exception | \Throwable $e) {
            DB::rollBack();
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('order.error_creating_general') . ' ' . $e->getMessage()
                ], 500);
            }
            return redirect()->back()->with('error', __('order.error_creating_general') . ' ' . $e->getMessage());
        }
    }

    /**
     * Display the specified order.
     *
     * This method retrieves an order by its ID, loads related models such as order items,
     * products, payments, customer, user, and sale returns. It returns the order details
     * in JSON format if the request expects JSON, or renders a view for displaying the order.
     *
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\JsonResponse|\Illuminate\View\View
     */
    public function show(Order $order)
    {
        $order->load(['orderItems.product', 'payments', 'customer', 'user', 'saleReturns']);

        if (request()->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Order retrieved successfully',
                'data' => $order
            ]);
        }

        return view('orders.show', compact('order'));
    }
}
