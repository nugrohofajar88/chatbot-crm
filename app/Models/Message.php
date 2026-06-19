<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected $fillable = [
        'conversation_id', 'direction', 'sender', 'body', 'type',
        'payload', 'wa_message_id',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /** URL lampiran untuk DITAMPILKAN (relatif, jalan di host/port apa pun). */
    public function mediaUrl(): ?string
    {
        $path = data_get($this->payload, 'path');

        return $path ? '/storage/'.ltrim($path, '/') : null;
    }
}
