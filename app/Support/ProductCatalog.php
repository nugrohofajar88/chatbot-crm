<?php

namespace App\Support;

use App\Models\Listing;

/**
 * Merangkai katalog produk menjadi teks ringkas untuk konteks AI.
 * Kosong → string kosong (sehingga tidak disuntik ke instruksi).
 */
class ProductCatalog
{
    public static function text(int $limit = 100): string
    {
        $items = Listing::where('status', '!=', 'nonaktif')
            ->orderBy('name')
            ->limit($limit)
            ->get();

        return $items->map(function (Listing $l) {
            $line = '- '.$l->name;
            if ($l->category) {
                $line .= ' ['.$l->category.']';
            }
            $line .= ': Rp '.number_format($l->price, 0, ',', '.').', stok '.$l->stock;
            if ($l->status === 'habis') {
                $line .= ' (HABIS)';
            }
            if ($l->description) {
                $line .= ' — '.$l->description;
            }

            return $line;
        })->implode("\n");
    }
}
