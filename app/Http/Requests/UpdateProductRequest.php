<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
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
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],

            'use_price_range' => ['nullable', 'boolean'],
            'price_min' => ['required_if:use_price_range,true', 'integer'],
            'price_max' => ['required_if:use_price_range,true', 'integer'],

            'use_quantity' => ['nullable', 'boolean'],
            'quantity' => ['required_if:use_quantity,true', 'integer'],

            'image_links' => ['nullable', 'array'],
            'image_links.*' => ['nullable', 'url'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
