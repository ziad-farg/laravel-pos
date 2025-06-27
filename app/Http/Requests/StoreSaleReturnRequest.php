<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSaleReturnRequest extends FormRequest
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
            'order_id'         => ['required', 'exists:orders,id'],
            'return_type'      => ['required', 'in:full_return,partial_return'],
            'returned_products' => ['required_if:return_type,partial_return', 'array'],
            'returned_products.*.product_id' => ['required_if:return_type,partial_return', 'exists:products,id'],
            'notes'            => ['nullable', 'string', 'max:500'],
        ];
    }
}
