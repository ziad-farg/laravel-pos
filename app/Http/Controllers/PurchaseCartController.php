<?php

namespace App\Http\Controllers;

use App\Models\PurchaseCart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\PurchaseCart\StorePurchaseCartRequest;
use App\Http\Requests\PurchaseCart\UpdateItemByProductIdRequest;


class PurchaseCartController extends Controller
{
    /**
     * Display the user's purchase cart.
     *
     * This method retrieves the purchase cart items for the authenticated user,
     * calculates the total after item discounts, and returns the data in JSON format
     * or as a view depending on the request type.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        try {
            $cartItems = PurchaseCart::with('product')
                ->where('user_id', Auth::id())
                ->get();

            $totalAfterItemDiscounts = $cartItems->sum(function ($item) {
                return $item->total_price_for_item;
            });

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => __('purchase_cart.cart_fetched_successfully'),
                    'data' => [
                        'user_id'                   => Auth::id(),
                        'total_items_count'         => $cartItems->count(),
                        'total_quantity'            => $cartItems->sum('quantity'),
                        'total_after_item_discounts' => round($totalAfterItemDiscounts, 2),
                        'items'                     => $cartItems->toArray(),
                    ],
                ], 200);
            }

            return view('purchase_cart.index', compact('cartItems', 'totalAfterItemDiscounts'));
        } catch (\Exception $e) {
            Log::error('Purchase Cart Fetch Error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return redirect()->back()->with('error', __('purchase_cart.failed_to_fetch_cart'));
        }
    }

    /**
     * Store or update an item in the user's purchase cart.
     *
     * This method validates the request data, checks if the item already exists in the cart,
     * and either updates the existing item or creates a new one. It returns a success message
     * along with the updated or newly created cart item.
     *
     * @param StorePurchaseCartRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StorePurchaseCartRequest $request)
    {
        try {
            $validated = $request->validated();

            $cartItem = PurchaseCart::where('user_id', Auth::id())
                ->where('product_id', $validated['product_id'])
                ->first();

            if ($cartItem) {
                $cartItem->quantity          += $validated['quantity'];
                $cartItem->cost_price_at_add = $validated['cost_price_at_add'];
                $cartItem->discount_type     = $validated['discount_type'] ?? null;
                $cartItem->discount_value    = $validated['discount_value'] ?? 0;
                $cartItem->save();
                $message = __('purchase_cart.item_updated_successfully');
            } else {
                $cartItem = PurchaseCart::create([
                    'user_id'           => Auth::id(),
                    'product_id'        => $validated['product_id'],
                    'quantity'          => $validated['quantity'],
                    'cost_price_at_add' => $validated['cost_price_at_add'],
                    'discount_type'     => $validated['discount_type'] ?? null,
                    'discount_value'    => $validated['discount_value'] ?? 0,
                ]);
                $message = __('purchase_cart.item_added_successfully');
            }

            $cartItem->load('product');

            return response()->json([
                'status' => 'success',
                'message' => $message,
                'cart_item' => $cartItem->toArray(),
            ], 200);
        } catch (\Exception $e) {
            Log::error('Purchase Cart Add/Update Error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return response()->json([
                'status' => 'error',
                'message' => __('purchase_cart.failed_to_process_item'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the quantity of an item in the user's purchase cart by product ID.
     *
     * This method validates the request data, checks if the item exists in the cart,
     * and updates its quantity. If the quantity is set to zero, it removes the item from the cart.
     * It returns a success message along with the updated cart item or an error message if the item is not found.
     *
     * @param UpdateItemByProductIdRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function changeQty(UpdateItemByProductIdRequest $request)
    {
        $validated = $request->validated();

        $cartItem = PurchaseCart::where('user_id', Auth::id())
            ->where('product_id', $validated['product_id'])
            ->first();

        if (!$cartItem) {
            return response()->json([
                'status' => 'error',
                'message' => __('purchase_cart.item_not_found'),
            ], 404);
        }

        try {
            if ($validated['quantity'] === 0) {
                $cartItem->delete();
                return response()->json([
                    'status' => 'success',
                    'message' => __('purchase_cart.item_removed_successfully'),
                ], 200);
            }

            $cartItem->quantity = $validated['quantity'];
            $cartItem->save();

            $cartItem->load('product');

            return response()->json([
                'status' => 'success',
                'message' => __('purchase_cart.item_updated_successfully'),
                'cart_item' => $cartItem->toArray(),
            ], 200);
        } catch (\Exception $e) {
            Log::error('Purchase Cart Update Quantity Error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return response()->json([
                'status' => 'error',
                'message' => __('purchase_cart.failed_to_update_item'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove an item from the user's purchase cart.
     *
     * This method checks if the authenticated user is authorized to delete the item,
     * and if so, it attempts to delete the item from the cart. It returns a success message
     * or an error message if the deletion fails.
     *
     * @param PurchaseCart $purchaseCart
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(PurchaseCart $purchaseCart)
    {
        if ($purchaseCart->user_id !== Auth::id()) {
            return response()->json([
                'status' => 'error',
                'message' => __('purchase_cart.unauthorized_access'),
            ], 403);
        }

        try {
            $purchaseCart->delete();

            return response()->json([
                'status' => 'success',
                'message' => __('purchase_cart.item_removed_successfully'),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('purchase_cart.failed_to_remove_item'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Empty the user's purchase cart.
     *
     * This method deletes all items in the authenticated user's purchase cart.
     * It returns a success message if the cart is cleared successfully or an error message if it fails.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function empty()
    {
        try {
            PurchaseCart::where('user_id', Auth::id())->delete();

            return response()->json([
                'status' => 'success',
                'message' => __('purchase_cart.cart_cleared_successfully'),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('purchase_cart.failed_to_clear_cart'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
