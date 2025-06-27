<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class ProductUpdateRequest extends FormRequest
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
        $product_id = $this->route('product');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('products')->ignore($product_id),
            ],
            'description' => 'nullable|string',
            'barcode' => [
                'required',
                'string',
                'max:50',
                Rule::unique('products')->ignore($product_id),
            ],
            'price' => 'required|regex:/^\d+(\.\d{1,2})?$/',
            'stock' => 'required|integer',
            'status' => 'required|boolean',
        ];
    }
}
