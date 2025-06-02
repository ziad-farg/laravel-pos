<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index(Request $request)
    {
        if ($request->wantsJson()) {
            return response(
                $request->user()->cart()->get()
            );
        }
        return view('cart.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.barcode' => 'required|string|exists:products,barcode',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.discount_percentage' => 'nullable|numeric|min:0|max:100',
        ]);

        $addedOrUpdatedProducts = [];

        foreach ($request->items as $cartItemData) {
            $barcode = $cartItemData['barcode'];
            $requestedQuantity = $cartItemData['quantity'];
            $discountPercentage = $cartItemData['discount_percentage'] ?? 0;

            $product = Product::where('barcode', $barcode)->first();

            if (!$product) {
                continue;
            }

            $cartItem = $request->user()->cart()->where('product_id', $product->id)->first();

            if ($cartItem) {
                $newQuantityInCart = $cartItem->pivot->quantity + $requestedQuantity;

                if ($product->quantity < $newQuantityInCart) {
                    continue;
                }

                $cartItem->pivot->quantity = $newQuantityInCart;
                $cartItem->pivot->discount_percentage = $discountPercentage;
                $cartItem->pivot->save();
            } else {

                if ($product->quantity < $requestedQuantity) {

                    continue;
                }

                $request->user()->cart()->attach($product->id, [
                    'quantity' => $requestedQuantity,
                    'discount_percentage' => $discountPercentage
                ]);
            }

            $addedOrUpdatedProducts[] = $product->id;
        }


        return response()->json([
            'message' => __('cart.items_processed_successfully'),
            'success' => true,
            'cart_items' => $request->user()->cart()->withPivot('quantity', 'discount_percentage')->get(),
        ], 200);
    }

    public function changeQty(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $product = Product::find($request->product_id);
        $cart = $request->user()->cart()->where('id', $request->product_id)->first();

        if ($cart) {
            // check product quantity
            if ($product->quantity < $request->quantity) {
                return response([
                    'message' => __('cart.available', ['quantity' => $product->quantity]),
                ], 400);
            }
            $cart->pivot->quantity = $request->quantity;
            $cart->pivot->save();
        }

        return response([
            'success' => true
        ]);
    }

    public function delete(Request $request)
    {
        $request->validate([
            'product_id' => 'required|integer|exists:products,id'
        ]);
        $request->user()->cart()->detach($request->product_id);

        return response('', 204);
    }

    public function empty(Request $request)
    {
        $request->user()->cart()->detach();

        return response('', 204);
    }
}
