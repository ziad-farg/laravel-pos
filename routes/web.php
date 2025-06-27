<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CartController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\TillController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\SaleReturnController;
use App\Http\Controllers\PurchaseCartController;

Route::get('/', function () {
    return redirect('/admin');
});

Auth::routes();

Route::prefix('admin')->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('home');
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingController::class, 'store'])->name('settings.store');
    Route::resource('products', ProductController::class);
    Route::resource('customers', CustomerController::class);
    Route::resource('orders', OrderController::class);
    Route::resource('suppliers', SupplierController::class);
    Route::resource('purchases', PurchaseController::class)->except(['edit', 'update', 'destroy']);
    Route::post('purchases/process', [PurchaseController::class, 'processPurchase'])->name('purchases.process');

    // User Cart Routes
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart', [CartController::class, 'store'])->name('cart.store');
    Route::post('/cart/change-qty', [CartController::class, 'changeQty']);
    Route::delete('/cart/delete', [CartController::class, 'delete']);
    Route::delete('/cart/empty', [CartController::class, 'empty']);


    // Purchase Cart Routes
    Route::prefix('purchase-cart')->group(function () {
        Route::get('/', [PurchaseCartController::class, 'index'])->name('purchase.cart.index');
        Route::post('/', [PurchaseCartController::class, 'store'])->name('purchase.cart.store');
        Route::post('/change-qty', [PurchaseCartController::class, 'changeQty'])->name('purchase.cart.change-qty');
        Route::delete('/delete/{purchaseCart}', [PurchaseCartController::class, 'delete'])->name('purchase.cart.delete');
        Route::delete('/empty', [PurchaseCartController::class, 'empty'])->name('purchase.cart.empty');
    });

    Route::post('/till/open', [TillController::class, 'openTill'])->name('till.open');
    Route::get('/till/start', [TillController::class, 'startTill'])->name('till.start');

    Route::get('/till/end', [TillController::class, 'endtTill'])->name('till.end');
    Route::post('/till/close', [TillController::class, 'closeTill'])->name('till.close');

    // Transaltions route for React component
    Route::get('/locale/{type}', function ($type) {
        $translations = trans($type);
        return response()->json($translations);
    });

    Route::prefix('returns')->group(function () {
        Route::get('/start', [SaleReturnController::class, 'startReturn'])->name('returns.start');
        Route::post('/process', [SaleReturnController::class, 'processReturn'])->name('returns.process');
        Route::get('/success', [SaleReturnController::class, 'showReturnSuccess'])->name('returns.success');
    });
});
