<?php

namespace App\Http\Requests;

use App\Models\Media;
use Illuminate\Foundation\Http\FormRequest;

class GetMediaStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Middleware handles authentication.
        // Ownership checks are performed explicitly during resource lookup
        // to avoid leaking resource existence (404 vs. 403).
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
            //
        ];
    }

    /**
     * Resolve the media resource within the context of the authenticated user.
     *
     * Ownership-based lookup is performed here instead of authorize()
     * to ensure a consistent 404 response when the resource does not exist
     * or does not belong to the user.
     */
    public function mediaOrFail(): Media
    {
        return Media::query()
                    ->where('id', $this->route('id'))
                    ->where('uploaded_by', $this->user()->id)
                    ->firstOrFail();
    }
}
