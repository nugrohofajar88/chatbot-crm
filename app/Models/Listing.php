<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Listing extends Model
{
    protected $fillable = [
        'title', 'location', 'beds', 'baths', 'area', 'price',
        'status', 'images', 'description',
    ];

    protected function casts(): array
    {
        return [
            'images' => 'array',
        ];
    }
}
