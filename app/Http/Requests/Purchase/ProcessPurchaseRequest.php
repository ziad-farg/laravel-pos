<?php

namespace App\Http\Requests\Purchase;

use App\Enums\DiscountType;
use App\Enums\PaymentStatus;
use Illuminate\Foundation\Http\FormRequest;

class ProcessPurchaseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'supplier_id'          => ['nullable', 'integer', 'exists:suppliers,id'],
            'invoice_number'       => ['nullable', 'string', 'unique:purchases,invoice_number'],
            'purchase_date'        => ['required', 'date'],
            'notes'                => ['nullable', 'string'],
            'paid_amount'          => ['nullable', 'numeric', 'min:0'],
            'payment_status'       => ['required', 'string', 'in:' . implode(',', PaymentStatus::values())],
            'invoice_discount_type' => ['nullable', 'string', 'in:' . implode(',', DiscountType::values())],
            'invoice_discount_value' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
