<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Katalog produk (sebelumnya "listing properti", kini generik).
 */
class Listing extends Model
{
    protected $fillable = [
        'name', 'category', 'price', 'stock', 'description', 'image_path', 'status',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'stock' => 'integer',
        ];
    }

    /** URL publik foto (disk public_uploads), atau null. */
    public function imageUrl(): ?string
    {
        return $this->image_path ? Storage::disk('public_uploads')->url($this->image_path) : null;
    }
}
