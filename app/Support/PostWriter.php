<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;

use function Laravel\Ai\agent;

/**
 * Penulis caption postingan media sosial (TERPISAH dari AiReply/persona chat).
 * Dari prompt operator -> satu caption FB/IG siap pakai.
 */
class PostWriter
{
    public static function generate(string $prompt): string
    {
        $instructions = <<<'TXT'
Anda copywriter media sosial untuk agen properti premium Aterra Realty di Indonesia.
Tulis SATU caption postingan Facebook/Instagram berdasarkan permintaan pengguna:
- Bahasa Indonesia, menarik, persuasif, dan natural (bukan kaku/robotik).
- Pakai emoji secukupnya dan 3-6 hashtag relevan di akhir.
- Jangan mengarang fakta spesifik (harga, alamat, luas) bila tidak diberikan.
- Hanya keluarkan teks caption-nya saja, tanpa label/penjelasan.
TXT;

        try {
            $res = agent(instructions: $instructions)->prompt($prompt);

            return trim($res->text);
        } catch (\Throwable $e) {
            Log::warning('post.ai.failed', ['error' => $e->getMessage()]);

            return '';
        }
    }
}
