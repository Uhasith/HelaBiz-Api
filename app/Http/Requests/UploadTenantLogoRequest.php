<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;

class UploadTenantLogoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $logo = $this->input('logo');
        $dataUriPattern = '/^data:image\/(\w+);base64,/';
        $hasValidFormat = is_string($logo) && preg_match($dataUriPattern, $logo);

        Log::info('UploadTenantLogoRequest received', [
            'user_id' => $this->user()?->id,
            'tenant_id' => $this->user()?->tenant_id,
            'has_logo' => ! empty($logo),
            'logo_length' => is_string($logo) ? strlen($logo) : 0,
            'is_base64_format' => $hasValidFormat,
            'all_keys' => array_keys($this->all()),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'logo' => ['required', 'string', 'regex:/^data:image\/(jpg|jpeg|png|svg|webp|heic|heif|gif);base64,/i'],
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'logo.required' => 'Please provide a logo to upload.',
            'logo.string' => 'The logo must be a base64 encoded string.',
            'logo.regex' => 'The logo must be a valid base64 data URI with format: data:image/{type};base64,... (supported: jpg, jpeg, png, svg, webp, heic, heif, gif).',
        ];
    }
}
