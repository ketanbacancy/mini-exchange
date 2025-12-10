<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use App\Enums\OrderSide;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'symbol' => ['required', 'string', 'max:10'],
            'side' => ['required', Rule::enum(OrderSide::class)],
            'price' => ['required', 'numeric', 'gt:0'],
            'amount' => ['required', 'numeric', 'gt:0'],
        ];
    }
}
