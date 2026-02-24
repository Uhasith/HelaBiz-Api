<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTenantRequest extends FormRequest
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
            'business_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:255'],
            'address' => ['required', 'string', 'max:500'],
            'city' => ['required', 'string', 'max:100'],
            'country' => ['required', 'string', 'max:100'],
            'currency' => ['required', 'string', 'size:3'],
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'business_name.required' => 'The business name field is required.',
            'business_name.max' => 'The business name must not exceed 255 characters.',
            'phone.required' => 'The phone field is required.',
            'phone.max' => 'The phone must not exceed 20 characters.',
            'email.required' => 'The email field is required.',
            'email.email' => 'The email must be a valid email address.',
            'address.required' => 'The address field is required.',
            'address.max' => 'The address must not exceed 500 characters.',
            'city.required' => 'The city field is required.',
            'city.max' => 'The city must not exceed 100 characters.',
            'country.required' => 'The country field is required.',
            'country.max' => 'The country must not exceed 100 characters.',
            'currency.required' => 'The currency field is required.',
            'currency.size' => 'The currency must be a 3-letter ISO 4217 code.',
        ];
    }
}
