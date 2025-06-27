<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Order;
use App\Enums\OrderStatus;
use App\Models\SaleReturn;
use App\Enums\DiscountType;
use App\Enums\SaleReturnType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StoreSaleReturnRequest;
use Illuminate\Validation\ValidationException;

class SaleReturnController extends Controller
{
    /**
     * Show the form for starting a return process.
     *
     * This method validates the order ID from the request,
     * checks if the order exists, and verifies if the return period is still valid.
     * If valid, it returns the order details for further processing.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function startReturn(Request $request)
    {
        try {
            $request->validate([
                'order_id' => 'required|integer|exists:orders,id',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed for order ID.',
                'errors' => $e->errors(),
            ], 422);
        }

        $order = Order::with('orderItems.product')->find($request->order_id);

        if (!$order) {
            return response()->json(['message' => 'Order not found.'], 404);
        }

        $orderDate = Carbon::parse($order->created_at);
        if ($orderDate->diffInDays(Carbon::now()) > 14) {
            return response()->json(['message' => 'The allowed return period (14 days) has expired for this order.'], 403);
        }

        return response()->json([
            'message' => 'Order details fetched successfully.',
            'order' => $order->toArray(),
        ], 200);
    }

    /**
     * Process the return of an order.
     *
     * This method handles both full and partial returns of an order.
     * It updates product stock, order status, and creates a sale return record.
     * It also calculates the refund amount based on the return type and items returned.
     *
     * @param StoreSaleReturnRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function processReturn(StoreSaleReturnRequest $request)
    {
        DB::beginTransaction();

        try {
            $order = Order::with('orderItems.product')->findOrFail($request->order_id);

            $totalRefundAmount = 0;
            $returnedItemsData = [];
            $saleReturn = null;



            $orderRawSubtotal = (float) $order->items_subtotal;



            $invoiceDiscountValue = (float) $order->invoice_discount_value;
            $totalOrderDiscountAmount = 0;

            if ($orderRawSubtotal > 0) {
                if ($order->invoice_discount_type === DiscountType::Fixed) {
                    $totalOrderDiscountAmount = $invoiceDiscountValue;
                } elseif ($order->invoice_discount_type === DiscountType::Percentage) {

                    $totalOrderDiscountAmount = $orderRawSubtotal * ($invoiceDiscountValue / 100);
                }
            }



            if ($request->return_type === SaleReturnType::FullReturn->value) {

                $calculatedRefundAmount = (float) $order->order_total;



                foreach ($order->orderItems as $orderItem) {
                    $product = $orderItem->product;

                    $remainingQuantity = $orderItem->quantity - $orderItem->quantity_returned;
                    if ($remainingQuantity <= 0) {
                        continue;
                    }

                    $product->stock += $remainingQuantity;
                    $product->save();

                    $orderItem->quantity_returned += $remainingQuantity;
                    $orderItem->save();

                    $returnedItemsData[] = [
                        'product_id'      => $product->id,
                        'quantity'        => $remainingQuantity,
                        'price_at_return' => (float) $orderItem->price,
                    ];
                }

                $order->status = OrderStatus::Fully_Returned->value;
                $order->returned_amount = $calculatedRefundAmount;
                $order->save();

                $saleReturn = SaleReturn::create([
                    'order_id'            => $order->id,
                    'type'                => $request->return_type,
                    'return_date'         => now(),
                    'total_refund_amount' => $calculatedRefundAmount,
                    'user_id'             => Auth::id(),
                    'notes'               => $request->notes,
                ]);
            } elseif ($request->return_type === SaleReturnType::PartialReturn->value) {

                foreach ($request->returned_products as $returnedProduct) {
                    $productId        = $returnedProduct['product_id'];
                    $quantityToReturn = $returnedProduct['quantity'];

                    $orderItem = $order->orderItems->first(function ($item) use ($productId) {
                        return $item->product_id === $productId;
                    });

                    if (!$orderItem) {
                        throw new \Exception("Product (ID: {$productId}) is not part of this original order.");
                    }

                    $product = $orderItem->product;

                    if ($quantityToReturn > ($orderItem->quantity - $orderItem->quantity_returned)) {
                        throw new \Exception("Cannot return {$quantityToReturn} item(s) of product '{$product->name}'. Remaining quantity to return is " . ($orderItem->quantity - $orderItem->quantity_returned) . ".");
                    }


                    $pricePerUnitAfterItemDiscount = (float) $orderItem->price;


                    $itemSubtotalValueBeforeInvoiceDiscount = $pricePerUnitAfterItemDiscount * $quantityToReturn;

                    $refundAmountForItem = $itemSubtotalValueBeforeInvoiceDiscount;




                    if ($orderRawSubtotal > 0 && $totalOrderDiscountAmount > 0) {

                        $discountRatioPerItem = $itemSubtotalValueBeforeInvoiceDiscount / $orderRawSubtotal;
                        $discountAttributableToItem = $totalOrderDiscountAmount * $discountRatioPerItem;
                        $refundAmountForItem = $itemSubtotalValueBeforeInvoiceDiscount - $discountAttributableToItem;
                    }

                    $refundAmountForItem = max(0, $refundAmountForItem);

                    $totalRefundAmount += $refundAmountForItem;

                    $product->stock += $quantityToReturn;
                    $product->save();

                    $orderItem->quantity_returned += $quantityToReturn;
                    $orderItem->save();

                    $returnedItemsData[] = [
                        'product_id'      => $product->id,
                        'quantity'        => $quantityToReturn,
                        'price_at_return' => $pricePerUnitAfterItemDiscount,
                    ];
                }



                $order->returned_amount += $totalRefundAmount;
                $allReturned = true;
                foreach ($order->orderItems as $item) {
                    if ($item->quantity > $item->quantity_returned) {
                        $allReturned = false;
                        break;
                    }
                }
                $order->status = $allReturned ? OrderStatus::Fully_Returned->value : OrderStatus::Partially_Returned->value;
                $order->save();

                $saleReturn = SaleReturn::create([
                    'order_id'            => $order->id,
                    'type'                => $request->return_type,
                    'return_date'         => Carbon::now(),
                    'total_refund_amount' => $totalRefundAmount,
                    'user_id'             => Auth::id(),
                    'notes'               => $request->notes,
                ]);
            } else {
                throw ValidationException::withMessages([
                    'return_type' => ['Invalid return type provided.'],
                ]);
            }

            if ($saleReturn) {
                foreach ($returnedItemsData as $itemData) {
                    $saleReturn->saleReturnItems()->create([
                        'product_id'      => $itemData['product_id'],
                        'quantity'        => $itemData['quantity'],
                        'price_at_return' => $itemData['price_at_return'],
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Return completed successfully.',
                'refund_amount' => number_format($saleReturn ? $saleReturn->total_refund_amount : 0, 2, '.', ''),
                'order_status' => $order->status,
                'return_id' => $saleReturn ? $saleReturn->id : null
            ], 200);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json(['message' => 'Validation Error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Sale Return Error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return response()->json(['message' => 'An error occurred during the return process.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Show the return success page.
     *
     * This method returns a view indicating that the return process was successful.
     *
     * @return \Illuminate\View\View
     */
    public function showReturnSuccess()
    {
        return view('returns.success');
    }
}
