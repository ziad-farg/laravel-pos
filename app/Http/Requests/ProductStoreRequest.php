<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductStoreRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'barcode' => 'required|string|max:50|unique:products',
            'price' => 'required|regex:/^\d+(\.\d{1,2})?$/',
            'stock' => 'required|integer',
            'status' => 'required|boolean',
        ];
    }
}
