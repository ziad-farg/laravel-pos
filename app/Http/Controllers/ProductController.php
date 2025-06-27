<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductStoreRequest;
use App\Http\Requests\ProductUpdateRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $productsQuery = Product::query();

        if ($request->has('search') && !empty($request->search)) {
            $products = $productsQuery->where('name', 'LIKE', "%{$request->search}%");
        }

        $products = $productsQuery->latest()->paginate(10);

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Products retrieved successfully',
                'data' => $products->items(),
                'pagination' => [
                    'total' => $products->total(),
                    'per_page' => $products->perPage(),
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'from' => $products->firstItem(),
                    'to' => $products->lastItem(),
                ],
            ]);
        }

        return view('products.index', compact('products'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('products.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(ProductStoreRequest $request)
    {
        $request->validate([
            'image' => 'nullable|image|max:2048',
        ]);

        $product = Product::create($request->validated());


        if ($request->hasFile('image')) {
            $image_path = $request->file('image')->store('products', 'public');

            $product->image()->create([
                'url' => $image_path,
            ]);
        }

        if (!$product) {
            return redirect()->back()->with('error', __('product.error_creating'));
        }

        return redirect()->route('products.index')->with('success', __('product.success_creating'));
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function show(Product $product)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function edit(Product $product)
    {
        return view('products.edit')->with('product', $product);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function update(ProductUpdateRequest $request, Product $product)
    {

        $request->validate([
            'image' => 'nullable|image|max:2048',
        ]);

        $product->update($request->validated());

        if ($request->hasFile('image')) {
            if ($product->image) {
                Storage::disk('public')->delete($product->image->url);
                $product->image()->delete();
            }

            $imagePath = $request->file('image')->store('product_images', 'public');
            $product->image()->create([
                'url' => $imagePath,
            ]);
        }

        if (!$product) {
            return redirect()->back()->with('error', __('product.error_updating'));
        }

        return redirect()->route('products.index')->with('success', __('product.success_updating'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function destroy(Product $product, Request $request)
    {
        if ($product->image) {
            Storage::disk('public')->delete($product->image->url);
            $product->image()->delete();
        }

        if (!$product->delete()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('product.error_deleting')
                ], 500);
            }
            return redirect()->back()->with('error', __('product.error_deleting'));
        }

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => __('product.success_deleting')
            ]);
        }

        return redirect()->route('products.index')->with('success', __('product.success_deleting'));
    }
}
