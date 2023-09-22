<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddImageProductRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'product_id' => ['required', 'string'],
            'images' => ['array', 'min:1', 'required'],
            'images.*' => ['required', 'image'],
        ];
    }
}
