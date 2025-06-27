<?php

namespace App\Http\Requests;

use App\Enums\OrderStatus;
use App\Enums\DiscountType;
use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;

class OrderStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'customer_id' => 'nullable|integer|exists:customers,id',
            'invoice_discount_type' => 'nullable|in:' . implode(',', DiscountType::values()),
            'invoice_discount_value' => 'nullable|numeric|min:0',
            'returned_amount' => 'nullable|numeric|min:0',
            'amount' => 'required|numeric|min:0.01',
            'till_id' => 'nullable|exists:tills,id',
            'payment_method' => 'required|string|in:' . implode(',', PaymentMethod::values()),
        ];
    }
}
