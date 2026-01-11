<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetMediaStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $media = $this->route('media');

        // Check if the media exists and belongs to the authenticated user
        return $media && $media->uploaded_by === auth()->id();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            //
        ];
    }
}
