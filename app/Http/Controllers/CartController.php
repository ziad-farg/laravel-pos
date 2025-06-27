<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Enums\DiscountType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\AddToCartRequest;
use App\Http\Requests\RemoveCartItemRequest;
use App\Http\Requests\ApplyInvoiceDiscountRequest;

class CartController extends Controller
{

    /**
     * Display the user's cart.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $userCart = $user->userCart()->firstOrCreate(['user_id' => $user->id]);

        $userCart->load(['items.product', 'customer']);

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => __('cart.retrieved_successfully'),
                'data' => $userCart
            ]);
        }

        return view('cart.index', compact('userCart'));
    }

    /**
     * Store items in the user's cart.
     *
     * @param AddToCartRequest $request
     * @return \Illuminate\Http\Response
     */
    public function store(AddToCartRequest $request)
    {
        try {
            DB::beginTransaction();

            $user = Auth::user();
            $userCart = $user->userCart()->firstOrCreate(['user_id' => $user->id]);

            foreach ($request->items as $itemData) {
                $barcode = $itemData['barcode'];
                $requestedQuantity = $itemData['quantity'];
                $discountValue = $itemData['discount_value'] ?? 0;
                $discountType = isset($itemData['discount_type']) ? DiscountType::from($itemData['discount_type']) : null;

                $product = Product::where('barcode', $barcode)->first();

                if (!$product) {
                    continue;
                }

                $cartItem = $userCart->items()->where('product_id', $product->id)->first();

                $currentProductPrice = $product->price;

                if ($cartItem) {
                    $newQuantityInCart = $cartItem->quantity + $requestedQuantity;

                    if ($product->stock < $newQuantityInCart) {
                        DB::rollBack();
                        return response()->json([
                            'status' => 'error',
                            'message' => __('cart.not_enough_stock_for_add', ['product' => $product->name, 'available' => $product->stock - $cartItem->quantity]),
                        ], 400);
                    }

                    $cartItem->update([
                        'quantity' => $newQuantityInCart,
                        'price_at_add' => $currentProductPrice,
                        'discount_type' => $discountType,
                        'discount_value' => $discountValue,
                    ]);
                } else {
                    if ($product->stock < $requestedQuantity) {
                        DB::rollBack();
                        return response()->json([
                            'status' => 'error',
                            'message' => __('cart.not_enough_stock', ['product' => $product->name, 'available' => $product->stock]),
                        ], 400);
                    }

                    $cartItem = $userCart->items()->create([
                        'product_id' => $product->id,
                        'quantity' => $requestedQuantity,
                        'price_at_add' => $currentProductPrice,
                        'discount_type' => $discountType,
                        'discount_value' => $discountValue,
                    ]);
                }
            }

            $userCart->update([
                'invoice_discount_type' => $request->input('invoice_discount_type'),
                'invoice_discount_value' => $request->input('invoice_discount_value', 0.0),
            ]);

            DB::commit();

            $userCart->load(['items.product', 'customer']);

            return response()->json([
                'status' => 'success',
                'message' => __('cart.items_processed_successfully'),
                'data' => $userCart,
            ], 200);
        } catch (\Exception | \Throwable $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => __('cart.error_processing_items') . ' ' . $e->getMessage()
            ], 500);
        }
    }


    /**
     * Change the quantity of an item in the user's cart.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function changeQty(Request $request)
    {
        try {
            DB::beginTransaction();

            $user = Auth::user();
            $userCart = $user->userCart;

            if (!$userCart) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => __('cart.no_active_cart_found')
                ], 400);
            }

            $productId = $request->product_id;
            $newQuantity = $request->quantity;

            $cartItem = $userCart->items()->where('product_id', $productId)->first();

            if (!$cartItem) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => __('cart.item_not_found_in_cart')
                ], 404);
            }

            $product = Product::find($productId);

            if (!$product) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => __('product.not_found')
                ], 404);
            }

            if ($product->stock < $newQuantity) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => __('cart.available', ['quantity' => $product->stock]),
                ], 400);
            }

            $cartItem->update(['quantity' => $newQuantity]);

            DB::commit();

            $userCart->load(['items.product', 'customer']);

            return response()->json([
                'status' => 'success',
                'message' => __('cart.quantity_updated_successfully'),
                'data' => $userCart
            ], 200);
        } catch (\Exception | \Throwable $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => __('cart.error_updating_quantity') . ' ' . $e->getMessage()
            ], 500);
        }
    }


    /**
     * Remove an item from the user's cart.
     *
     * @param RemoveCartItemRequest $request
     * @return \Illuminate\Http\Response
     */
    public function delete(RemoveCartItemRequest $request)
    {
        try {
            DB::beginTransaction();

            $user = Auth::user();
            $userCart = $user->userCart;

            if (!$userCart) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => __('cart.no_active_cart_found')
                ], 400);
            }

            $productId = $request->product_id;

            $deletedCount = $userCart->items()->where('product_id', $productId)->delete();

            if ($deletedCount == 0) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => __('cart.item_not_found_in_cart')
                ], 404);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => __('cart.item_removed_successfully')
            ], 200);
        } catch (\Exception | \Throwable $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => __('cart.error_removing_item') . ' ' . $e->getMessage()
            ], 500);
        }
    }


    /**
     * Empty the user's cart.
     *
     * @return \Illuminate\Http\Response
     */
    public function empty()
    {
        try {
            DB::beginTransaction();

            $user = Auth::user();
            $userCart = $user->userCart;

            if (!$userCart) {
                DB::rollBack();
                return response()->json([
                    'status' => 'success',
                    'message' => __('cart.cart_already_empty')
                ], 200);
            }

            $userCart->items()->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => __('cart.cart_emptied_successfully')
            ], 200);
        } catch (\Exception | \Throwable $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => __('cart.error_emptying_cart') . ' ' . $e->getMessage()
            ], 500);
        }
    }


    /**
     * Apply invoice discount to the user's cart.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function applyInvoiceDiscount(ApplyInvoiceDiscountRequest $request)
    {
        $user = Auth::user();
        $userCart = $user->userCart;

        if (!$userCart) {
            return response()->json([
                'status' => 'error',
                'message' => __('cart.no_active_cart_found')
            ], 404);
        }

        $validatedData = $request->validated();

        $userCart->update([
            'invoice_discount_type' => $validatedData['invoice_discount_type'],
            'invoice_discount_value' => $validatedData['invoice_discount_value'],
        ]);

        $userCart->load(['items.product', 'customer']);

        return response()->json([
            'status' => 'success',
            'message' => 'Invoice discount applied to cart successfully.',
            'data' => $userCart
        ]);
    }
}
