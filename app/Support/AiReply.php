<?php

namespace App\Support;

use App\Models\Conversation;
use Illuminate\Support\Facades\Log;

use function Laravel\Ai\agent;

/**
 * Menghasilkan SATU balasan AI untuk sebuah percakapan, lintas channel
 * (WhatsApp / Messenger / Instagram). Memakai persona dari AiPersona.
 * Mengembalikan '' bila AI gagal atau balasannya kosong (dicatat ke log).
 */
class AiReply
{
    public static function generate(Conversation $conv): string
    {
        $conv->load(['messages' => fn ($q) => $q->orderBy('id')]);

        $history = $conv->messages
            ->map(fn ($m) => ($m->sender === 'lead' ? 'Pengguna' : 'Asisten').': '.$m->body)
            ->implode("\n");

        $prompt = "Riwayat percakapan:\n{$history}\n\n"
            .'Tulis SATU balasan terbaik untuk pesan terakhir pengguna, sesuai peranmu. Hanya teks balasannya, tanpa label.';

        try {
            $res = agent(instructions: AiPersona::instructions())->prompt($prompt);

            return trim(preg_replace('/^["\']|["\']$/', '', $res->text));
        } catch (\Throwable $e) {
            Log::warning('ai.reply.failed', ['conversation' => $conv->id, 'error' => $e->getMessage()]);

            return '';
        }
    }

    /** Balasan AI singkat untuk komentar PUBLIK (Instagram). '' bila gagal. */
    public static function comment(string $commentText, ?string $username = null): string
    {
        $who = $username ? "@{$username}" : 'seseorang';

        $prompt = "Seseorang ({$who}) menulis komentar publik di postingan kami:\n"
            ."\"{$commentText}\"\n\n"
            .'Tulis SATU balasan komentar yang SANGAT singkat (maks 1-2 kalimat), ramah, sesuai peranmu. '
            .'Ini PUBLIK: jangan bagikan info sensitif; bila relevan, ajak lanjut lewat DM. Hanya teks balasannya, tanpa label.';

        try {
            $res = agent(instructions: AiPersona::instructions())->prompt($prompt);

            return trim(preg_replace('/^["\']|["\']$/', '', $res->text));
        } catch (\Throwable $e) {
            Log::warning('ai.comment.failed', ['error' => $e->getMessage()]);

            return '';
        }
    }
}
