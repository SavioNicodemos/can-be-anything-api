<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use JetBrains\PhpStorm\ArrayShape;

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
    #[ArrayShape(['name' => "string[]", 'description' => "string[]", 'price_min' => "string[]", 'price_max' => "string[]", 'quantity' => "string[]", 'image_links' => "string[]", 'image_links.*' => "string[]", 'is_active' => "string[]"])]
    public function rules(): array
    {
        return [
            'name' => ['string'],
            'description' => ['string'],
            'price_min' => ['nullable', 'integer'],
            'price_max' => ['nullable', 'integer'],
            'quantity' => ['nullable', 'integer'],
            'image_links' => ['nullable', 'array'],
            'image_links.*' => ['nullable', 'url'],
            'is_active' => ['boolean'],
        ];
    }
}
