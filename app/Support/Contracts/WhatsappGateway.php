<?php

namespace App\Support\Contracts;

/**
 * Kontrak gateway WhatsApp. Implementasi: FonnteService / WablasService.
 * Driver aktif dipilih via config services.whatsapp.driver (WHATSAPP_DRIVER).
 */
interface WhatsappGateway
{
    /** Kirim pesan teks. */
    public function sendMessage(string $phone, string $message): bool;

    /** Kirim media (gambar/dokumen) dari URL publik. */
    public function sendMedia(string $phone, string $url, string $filename, string $caption = ''): bool;

    /** Normalkan nomor (08xx -> 62xx). */
    public function normalize(string $phone): string;
}
