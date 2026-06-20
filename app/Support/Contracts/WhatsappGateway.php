<?php

namespace App\Support\Contracts;

/**
 * Kontrak gateway WhatsApp. Implementasi: FonnteService / WablasService.
 * Driver aktif dipilih via config services.whatsapp.driver (WHATSAPP_DRIVER).
 */
interface WhatsappGateway
{
    /** Kirim pesan teks. Mengembalikan message id gateway bila sukses, null bila gagal. */
    public function sendMessage(string $phone, string $message): ?string;

    /** Kirim media (gambar/dokumen) dari URL publik. Mengembalikan message id atau null. */
    public function sendMedia(string $phone, string $url, string $filename, string $caption = ''): ?string;

    /** Normalkan nomor (08xx -> 62xx). */
    public function normalize(string $phone): string;
}
