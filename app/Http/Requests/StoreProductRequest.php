<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use JetBrains\PhpStorm\ArrayShape;

class StoreProductRequest extends FormRequest
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
            'name' => ['required', 'string'],
            'description' => ['required', 'string'],

            'use_price_range' => ['required', 'boolean'],
            'price_min' => ['required_if:use_price_range,true', 'integer'],
            'price_max' => ['required_if:use_price_range,true', 'integer'],

            'use_quantity' => ['required', 'boolean'],
            'quantity' => ['required_if:use_quantity,true', 'integer'],

            'image_links' => ['nullable', 'array'],
            'image_links.*' => ['nullable', 'url'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
