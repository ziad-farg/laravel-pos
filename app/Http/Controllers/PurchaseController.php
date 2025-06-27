<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\PurchaseCart;
use App\Models\Supplier;
use App\Enums\DiscountType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Purchase\ProcessPurchaseRequest;

class PurchaseController extends Controller
{


    /**
     * Display a listing of purchases.
     *
     * Retrieves a paginated list of purchases, including related supplier and user data,
     * ordered by purchase date in descending order. If the request expects a JSON response,
     * returns the purchases as JSON with a status and message. Otherwise, renders the
     * purchases index view.
     *
     * @param  \Illuminate\Http\Request  $request  The incoming HTTP request.
     * @return \Illuminate\Http\JsonResponse|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        $purchases = Purchase::with('supplier', 'user')
            ->orderBy('purchase_date', 'desc')
            ->paginate($request->get('limit', 10));

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => __('purchase.purchases_list_fetched'),
                'purchases' => $purchases,
            ], 200);
        }

        return view('purchases.index', compact('purchases'));
    }

    /**
     * Show the form for creating a new purchase.
     *
     * Retrieves the user's purchase cart items and all suppliers,
     * calculates the total after item discounts, and returns the
     * data to the purchase creation view.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $cartItems = PurchaseCart::with('product')->where('user_id', Auth::id())->get();
        $suppliers = Supplier::all();

        $totalAfterItemDiscounts = $cartItems->sum(function ($item) {
            return $item->total_price_for_item;
        });

        return view('purchases.create', compact('cartItems', 'suppliers', 'totalAfterItemDiscounts'));
    }

    /**
     * Store a newly created purchase in storage.
     *
     * Validates the request data, processes the purchase by creating a new Purchase record,
     * updating product stocks, and clearing the user's purchase cart. If successful, returns
     * a success message with the purchase ID. If an error occurs, rolls back the transaction
     * and returns an error message.
     *
     * @param  \App\Http\Requests\Purchase\ProcessPurchaseRequest  $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function store(ProcessPurchaseRequest $request)
    {
        DB::beginTransaction();

        try {
            $userId = Auth::id();
            $cartItems = PurchaseCart::where('user_id', $userId)->get();

            if ($cartItems->isEmpty()) {
                throw new \Exception(__('purchase.cart_is_empty'));
            }

            $validated = $request->validated();

            $totalAmountBeforeDiscount = 0;
            foreach ($cartItems as $item) {
                $totalAmountBeforeDiscount += $item->total_price_for_item;
            }

            $invoiceDiscountValue = (float)($validated['invoice_discount_value'] ?? 0);
            $invoiceDiscountType  = $validated['invoice_discount_type'] ?? null;
            $totalPurchaseDiscount = 0;

            if ($invoiceDiscountValue > 0 && $totalAmountBeforeDiscount > 0) {
                if ($invoiceDiscountType === DiscountType::Fixed->value) {
                    $totalPurchaseDiscount = $invoiceDiscountValue;
                } elseif ($invoiceDiscountType === DiscountType::Percentage->value) {
                    $totalPurchaseDiscount = $totalAmountBeforeDiscount * ($invoiceDiscountValue / 100);
                }
            }

            $finalTotalAmount = max(0, $totalAmountBeforeDiscount - $totalPurchaseDiscount);
            $paidAmount = (float) ($validated['paid_amount'] ?? 0);
            if ($paidAmount > $finalTotalAmount) {
                $paidAmount = $finalTotalAmount;
            }

            $purchase = Purchase::create([
                'user_id'               => $userId,
                'supplier_id'           => $validated['supplier_id'],
                'invoice_number'        => $validated['invoice_number'],
                'total_amount'          => $finalTotalAmount,
                'paid_amount'           => $paidAmount,
                'payment_status'        => $validated['payment_status'],
                'purchase_date'         => $validated['purchase_date'],
                'notes'                 => $validated['notes'],
                'invoice_discount_type' => $invoiceDiscountType,
                'invoice_discount_value' => $invoiceDiscountValue,
            ]);

            foreach ($cartItems as $cartItem) {
                $product = $cartItem->product;

                $purchase->items()->create([
                    'product_id'        => $cartItem->product_id,
                    'quantity'          => $cartItem->quantity,
                    'cost_price'        => $cartItem->cost_price_at_add,
                    'item_discount_type' => $cartItem->discount_type,
                    'item_discount_value' => $cartItem->discount_value,
                ]);

                $product->stock += $cartItem->quantity;
                $product->save();
            }

            PurchaseCart::where('user_id', $userId)->delete();

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => __('purchase.purchase_completed_successfully'),
                    'purchase_id' => $purchase->id,
                ], 200);
            }

            return redirect()->route('purchases.show', $purchase->id)
                ->with('success', __('purchase.purchase_completed_successfully'));
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', __('purchase.failed_to_process_purchase'))
                ->withInput();
        }
    }

    /**
     * Display the specified purchase.
     *
     * This method retrieves a purchase by its ID, loads related models such as items, products,
     * supplier, and user. It returns the purchase details in JSON format if the request expects
     * JSON, or renders a view for displaying the purchase.
     *
     * @param  \App\Models\Purchase  $purchase
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\View\View
     */
    public function show(Purchase $purchase, Request $request)
    {
        $purchase->load('items.product', 'supplier', 'user');

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => __('purchase.purchase_details_fetched'),
                'purchase' => $purchase->toArray(),
            ], 200);
        }

        return view('purchases.show', compact('purchase'));
    }
}
