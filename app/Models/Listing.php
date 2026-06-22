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
        'name', 'category', 'price', 'stock', 'description', 'media', 'status',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'stock' => 'integer',
            'media' => 'array',
        ];
    }

    /** URL gambar sampul (gambar pertama di media), atau null. */
    public function coverUrl(): ?string
    {
        foreach ($this->media ?? [] as $m) {
            if (($m['is_image'] ?? false) && ! empty($m['path'])) {
                return Storage::disk('public_uploads')->url($m['path']);
            }
        }

        return null;
    }

    /**
     * Daftar media siap tampil: [{url, name, is_image}].
     *
     * @return array<int, array<string, mixed>>
     */
    public function mediaItems(): array
    {
        return collect($this->media ?? [])->map(fn ($m) => [
            'url' => Storage::disk('public_uploads')->url($m['path'] ?? ''),
            'name' => $m['name'] ?? '',
            'is_image' => (bool) ($m['is_image'] ?? false),
        ])->all();
    }
}
