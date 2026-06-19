<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Sumber tunggal "persona" / instruksi sistem untuk AI Aterra.
 * Nilai dapat diatur operator lewat menu Persona AI (disimpan di tabel settings).
 * Jika belum diatur, dipakai DEFAULT di bawah.
 */
class AiPersona
{
    public const KEY = 'ai_persona';

    public const DEFAULT = <<<'TXT'
Anda asisten AI untuk agen properti premium Aterra Realty di Indonesia.
Balas dalam Bahasa Indonesia yang sopan, hangat, dan profesional. Singkat (maks 2-3 kalimat).
Tujuan: menjawab pertanyaan calon pembeli, mengkualifikasi kebutuhan, dan mengarahkan ke viewing/penjadwalan.
Jangan mengarang fakta properti yang tidak ada di konteks; jika tidak tahu, tawarkan untuk mengeceknya.
TXT;

    /** Instruksi aktif (dari settings, fallback ke DEFAULT). */
    public static function instructions(): string
    {
        $value = trim((string) Setting::get(self::KEY, self::DEFAULT));

        return $value !== '' ? $value : self::DEFAULT;
    }
}
