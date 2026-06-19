<?php

namespace App\Support;

use App\Models\Conversation;
use App\Models\LeadScore;
use Illuminate\Support\Facades\Log;

use function Laravel\Ai\agent;

/**
 * Menilai lead (lead scoring) dari isi percakapan memakai structured output
 * Laravel AI SDK. AI mengembalikan angka pasti per dimensi (0-100).
 * Hasil: estimasi/heuristik untuk memprioritaskan lead, bukan angka eksak.
 */
class LeadScoringService
{
    public function score(Conversation $conv): ?LeadScore
    {
        $conv->load(['messages' => fn ($q) => $q->orderBy('id'), 'contact']);

        if ($conv->messages->isEmpty()) {
            return null;
        }

        $history = $conv->messages
            ->map(fn ($m) => ($m->sender === 'lead' ? 'Calon pembeli' : 'Agen').': '.$m->body)
            ->implode("\n");

        $instructions = <<<'TXT'
Anda analis lead untuk agen properti premium Aterra Realty di Indonesia.
Nilai SATU lead berdasarkan percakapan, skala 0-100 per dimensi:
- budget: kejelasan & realisme niat anggaran (sebut angka jelas = tinggi; tanpa info = rendah).
- engagement: keaktifan & kedalaman minat (banyak pertanyaan, respons cepat, detail = tinggi).
- urgency: seberapa cepat ingin bertindak (minta viewing/closing segera = tinggi; "lihat-lihat dulu" = rendah).
- total: penilaian gabungan keseluruhan 0-100.
- temperature: "hot" jika total >= 75, "warm" jika 50-74, "cold" jika < 50.
- alasan: satu kalimat singkat alasan penilaian, Bahasa Indonesia.
Jika informasi minim, beri skor rendah. Jangan mengarang.
TXT;

        $prompt = "Riwayat percakapan:\n{$history}\n\nNilai lead ini sekarang.";

        try {
            $res = agent(
                instructions: $instructions,
                schema: fn ($schema) => [
                    'budget' => $schema->integer()->min(0)->max(100),
                    'engagement' => $schema->integer()->min(0)->max(100),
                    'urgency' => $schema->integer()->min(0)->max(100),
                    'total' => $schema->integer()->min(0)->max(100),
                    'temperature' => $schema->string()->enum(['hot', 'warm', 'cold']),
                    'alasan' => $schema->string(),
                ],
            )->prompt($prompt);

            $data = $res->toArray();
        } catch (\Throwable $e) {
            Log::warning('lead.scoring.failed', ['conversation' => $conv->id, 'error' => $e->getMessage()]);

            return null;
        }

        $budget = $this->clamp($data['budget'] ?? 0);
        $engagement = $this->clamp($data['engagement'] ?? 0);
        $urgency = $this->clamp($data['urgency'] ?? 0);
        $total = $this->clamp($data['total'] ?? (int) round(($budget + $engagement + $urgency) / 3));

        $temperature = $data['temperature'] ?? null;
        if (! in_array($temperature, ['hot', 'warm', 'cold'], true)) {
            $temperature = $total >= 75 ? 'hot' : ($total >= 50 ? 'warm' : 'cold');
        }

        $leadScore = $conv->scores()->create([
            'budget' => $budget,
            'engagement' => $engagement,
            'urgency' => $urgency,
            'total' => $total,
        ]);

        $conv->update([
            'score' => $total,
            'temperature' => $temperature,
        ]);

        Log::info('lead.scoring.done', [
            'conversation' => $conv->id,
            'total' => $total,
            'temperature' => $temperature,
            'alasan' => $data['alasan'] ?? null,
        ]);

        return $leadScore;
    }

    private function clamp(mixed $n): int
    {
        return max(0, min(100, (int) $n));
    }
}
