<?php namespace App\Models;

use App\Enum\MediaStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'uploaded_by',
        'title',
        'description',
        'type',
        'original_path',
        'thumbnail_path',
        'status',
        'error_message',
    ];

    protected $casts = [
        'status' => MediaStatus::class,
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getOriginalUrlAttribute(): ?string
    {
        return $this->original_path ? Storage::disk('public')->url($this->original_path) : null;
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        return $this->thumbnail_path ? Storage::disk('public')->url($this->thumbnail_path) : null;
    }
}