<?php namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MediaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'status'         => $this->status,
            'type'           => $this->type,
            'original_path'  => url($this->original_path),
            'thumbnail_path' => url($this->thumbnail_path),
            'error_message'  => $this->error_message,
            'status_url'     => url("/api/media/{$this->id}/status"),
            'uploaded_at'    => $this->created_at,
        ];
    }
}
