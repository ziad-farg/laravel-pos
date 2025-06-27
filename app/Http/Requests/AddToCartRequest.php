<?php

namespace App\Http\Requests;

use App\Enums\DiscountType;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Http\FormRequest;

class AddToCartRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'items' => 'required|array|min:1',
            'items.*.barcode' => 'required|string|exists:products,barcode',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.discount_type' => ['nullable', Rule::in(array_column(DiscountType::cases(), 'value'))],
            'items.*.discount_value' => 'nullable|numeric|min:0',
            'invoice_discount_type' => 'nullable|in:' . implode(',', DiscountType::values()),
            'invoice_discount_value' => 'nullable|numeric|min:0',
        ];
    }
}
