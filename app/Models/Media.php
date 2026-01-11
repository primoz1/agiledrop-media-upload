<?php namespace App\Models;

use App\Enum\MediaStatus;
use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    protected $fillable = [
        'title',
        'description',
        'uploaded_by',
        'type',
        'original_path',
        'thumbnail_path',
        'status',
        'error_message',
    ];

    protected $casts = [
        'status' => MediaStatus::class,
    ];
}