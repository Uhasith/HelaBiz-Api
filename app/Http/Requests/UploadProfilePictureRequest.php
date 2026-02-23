<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;

class UploadProfilePictureRequest extends FormRequest
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
        $picture = $this->input('picture');
        $dataUriPattern = '/^data:image\/(\w+);base64,/';
        $hasValidFormat = is_string($picture) && preg_match($dataUriPattern, $picture);
        
        Log::info('UploadProfilePictureRequest received', [
            'user_id' => $this->user()?->id,
            'has_picture' => !empty($picture),
            'picture_length' => is_string($picture) ? strlen($picture) : 0,
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
            'picture' => ['required', 'string', 'regex:/^data:image\/(jpg|jpeg|png|webp|heic|heif|gif);base64,/i'],
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'picture.required' => 'Please provide a profile picture to upload.',
            'picture.string' => 'The picture must be a base64 encoded string.',
            'picture.regex' => 'The picture must be a valid base64 data URI with format: data:image/{type};base64,... (supported: jpg, jpeg, png, webp, heic, heif, gif).',
        ];
    }
}
