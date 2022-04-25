<?php

namespace EscolaLms\Images\Models;

use EscolaLms\Images\Database\Factories\ImageCacheFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImageCache extends Model
{
    use HasFactory;

    protected $fillable = [
        'path',
        'hash_path',
    ];

    protected static function newFactory(): ImageCacheFactory
    {
        return ImageCacheFactory::new();
    }
}
