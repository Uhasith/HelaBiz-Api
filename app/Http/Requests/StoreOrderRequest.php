<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:20', 'required_without:customer_email'],
            'customer_email' => ['nullable', 'email', 'max:255', 'required_without:customer_phone'],
            'address' => ['nullable', 'string', 'max:500'],
            'order_date' => ['required', 'date'],
            'status' => ['required', 'in:pending,processing,completed,cancelled'],
            'subtotal' => ['required', 'numeric', 'min:0'],
            'tax' => ['required', 'numeric', 'min:0'],
            'discount' => ['required', 'numeric', 'min:0'],
            'total' => ['required', 'numeric', 'min:0'],
            'warranty_period' => ['nullable', 'integer', 'min:1'],
            'warranty_unit' => ['nullable', 'in:days,weeks,months,years', 'required_with:warranty_period'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.total' => ['required', 'numeric', 'min:0'],
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'customer_phone.required_without' => 'Either customer phone or email must be provided.',
            'customer_email.required_without' => 'Either customer phone or email must be provided.',
            'warranty_unit.required_with' => 'Warranty unit is required when warranty period is specified.',
            'items.required' => 'At least one item is required.',
            'items.min' => 'At least one item is required.',
            'items.*.product_id.exists' => 'The selected product does not exist.',
        ];
    }
}
