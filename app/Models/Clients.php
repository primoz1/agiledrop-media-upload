<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Clients extends Model
{
    protected $fillable = ['name', 'slug'];

    public function media(): HasMany
    {
        return $this->hasMany(Media::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
