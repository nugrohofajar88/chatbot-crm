<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $fillable = [
        'prompt', 'caption', 'image_path', 'platforms',
        'status', 'fb_post_id', 'ig_post_id', 'result',
    ];

    protected function casts(): array
    {
        return [
            'platforms' => 'array',
            'result' => 'array',
        ];
    }

    /** URL publik gambar (untuk preview & dikirim ke Meta). */
    public function imageUrl(): ?string
    {
        return $this->image_path ? url('uploads/'.$this->image_path) : null;
    }
}
