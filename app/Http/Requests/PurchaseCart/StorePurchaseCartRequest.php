<?php

namespace App\Http\Requests\PurchaseCart;

use App\Enums\DiscountType;
use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseCartRequest extends FormRequest
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
            'product_id'        => ['required', 'integer', 'exists:products,id'],
            'quantity'          => ['required', 'integer', 'min:1'],
            'cost_price_at_add' => ['required', 'numeric', 'min:0'],
            'discount_type'     => ['nullable', 'string', 'in:' . implode(',', DiscountType::values())],
            'discount_value'    => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
