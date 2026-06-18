<?php

namespace Database\Seeders;

use App\Models\Contact;
use App\Models\Listing;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $leads = [
            [
                'name' => 'Budi Santoso', 'phone' => '6281210994421', 'email' => 'budi.santoso@gmail.com',
                'prefs' => ['Min. 2 kamar tidur', 'Area SCBD / Senayan', 'Lantai tinggi, city view'],
                'conv' => [
                    'stage' => 'viewing', 'temperature' => 'hot', 'score' => 88, 'unread' => 2, 'ai_enabled' => true,
                    'summary' => 'Lead panas. Mencari hunian keluarga di area SCBD, budget Rp 15-20 M, siap viewing akhir pekan ini.',
                    'score_breakdown' => ['budget' => 90, 'engagement' => 88, 'urgency' => 84],
                ],
                'messages' => [
                    ['lead', 'Selamat siang, saya tertarik unit di The Pakubuwono Signature. Apakah masih tersedia?'],
                    ['ai', 'Selamat siang Pak Budi! Unit 2BR (142 m2) masih tersedia - lantai tinggi city view, Rp 18,5 M. Apakah Bapak ingin saya jadwalkan viewing?'],
                    ['lead', 'Boleh, weekend ini bisa?'],
                    ['lead', 'Sekalian saya mau tanya, ada pilihan 3BR juga?'],
                ],
            ],
            [
                'name' => 'Maya Wijaya', 'phone' => '6281322448890', 'email' => 'maya.wijaya@gmail.com',
                'prefs' => ['Villa di Bali', 'Potensi sewa harian', 'Sudah furnished'],
                'conv' => [
                    'stage' => 'terkualifikasi', 'temperature' => 'warm', 'score' => 64, 'unread' => 0, 'ai_enabled' => true,
                    'summary' => 'Lead hangat. Mencari villa investasi di Bali untuk disewakan harian, masih membandingkan area Canggu & Ubud.',
                    'score_breakdown' => ['budget' => 68, 'engagement' => 70, 'urgency' => 54],
                ],
                'messages' => [
                    ['lead', 'Hi, lihat Villa Tepi Sawah. Masih ada?'],
                    ['ai', 'Halo Kak Maya! Villa Tepi Sawah di Canggu masih tersedia - 3 kamar, private pool. Rp 12,9 M, cocok untuk sewa harian.'],
                    ['lead', 'Apakah villa ini sudah termasuk furnished?'],
                ],
            ],
            [
                'name' => 'Andre Tanudjaja', 'phone' => '6281187653210', 'email' => 'andre.t@outlook.com',
                'prefs' => ['Townhouse BSD City', 'Pembayaran cash keras', 'Closing bulan ini'],
                'conv' => [
                    'stage' => 'negosiasi', 'temperature' => 'hot', 'score' => 81, 'unread' => 1, 'ai_enabled' => false,
                    'summary' => 'Lead serius di tahap negosiasi. Siap bayar cash untuk townhouse di BSD City, ingin closing bulan ini.',
                    'score_breakdown' => ['budget' => 86, 'engagement' => 80, 'urgency' => 78],
                ],
                'messages' => [
                    ['lead', 'Saya minat Townhouse Foresta yang kemarin di-share.'],
                    ['ai', 'Baik Pak Andre. Townhouse Foresta BSD, 3 lantai, 4 KT, Rp 4,8 M, sudah SHM. Ingin saya hubungkan untuk negosiasi harga?'],
                    ['lead', 'Kalau cash keras, best price berapa ya?'],
                ],
            ],
            [
                'name' => 'Sari Kusuma', 'phone' => '6285677881122', 'email' => 'sari.k@gmail.com',
                'prefs' => ['Studio / 1 KT', 'Area Bintaro', 'Budget terbatas'],
                'conv' => [
                    'stage' => 'baru', 'temperature' => 'cold', 'score' => 38, 'unread' => 0, 'ai_enabled' => true,
                    'summary' => 'Lead baru tahap awal. Masih riset, belum ada urgensi. Cocok untuk nurturing jangka panjang.',
                    'score_breakdown' => ['budget' => 40, 'engagement' => 36, 'urgency' => 30],
                ],
                'messages' => [
                    ['lead', 'Halo, ada apartemen studio area Bintaro?'],
                    ['ai', 'Halo Kak Sari! Ada studio di Bintaro Park View, 24 m2, Rp 720 jt, fully furnished. Mau saya kirim detailnya?'],
                    ['lead', 'Masih lihat-lihat dulu, nanti saya kabari.'],
                ],
            ],
        ];

        foreach ($leads as $i => $lead) {
            $contact = Contact::create([
                'name' => $lead['name'],
                'phone' => $lead['phone'],
                'email' => $lead['email'],
                'channel' => 'whatsapp',
                'prefs' => $lead['prefs'],
                'lead_since' => now()->subDays(7 - $i),
            ]);

            $conv = $contact->conversations()->create([
                'channel' => 'whatsapp',
                'stage' => $lead['conv']['stage'],
                'temperature' => $lead['conv']['temperature'],
                'score' => $lead['conv']['score'],
                'unread' => $lead['conv']['unread'],
                'ai_enabled' => $lead['conv']['ai_enabled'],
                'summary' => $lead['conv']['summary'],
                'last_message_at' => now()->subMinutes($i * 17),
            ]);

            $t = now()->subMinutes(count($lead['messages']) * 5 + $i * 17);
            foreach ($lead['messages'] as $m) {
                $conv->messages()->create([
                    'direction' => $m[0] === 'lead' ? 'in' : 'out',
                    'sender' => $m[0],
                    'body' => $m[1],
                    'type' => 'text',
                    'created_at' => $t,
                    'updated_at' => $t,
                ]);
                $t = $t->copy()->addMinutes(5);
            }

            $conv->scores()->create($lead['conv']['score_breakdown'] + ['total' => $lead['conv']['score']]);
        }

        $listings = [
            ['The Pakubuwono Signature', 'SCBD, Jakarta Selatan', 2, 2, 142, 18_500_000_000, 'available'],
            ['Villa Tepi Sawah', 'Canggu, Bali', 3, 3, 280, 12_900_000_000, 'hot'],
            ['Townhouse Foresta', 'BSD City, Tangerang', 4, 3, 210, 4_800_000_000, 'negotiation'],
            ['Senayan Residence', 'Senayan, Jakarta Pusat', 3, 2, 176, 21_500_000_000, 'available'],
            ['Bintaro Park View', 'Bintaro, Tangerang Selatan', 1, 1, 24, 720_000_000, 'available'],
            ['Ubud Jungle Villa', 'Ubud, Bali', 2, 2, 190, 9_400_000_000, 'sold'],
        ];
        foreach ($listings as $l) {
            Listing::create([
                'title' => $l[0], 'location' => $l[1], 'beds' => $l[2], 'baths' => $l[3],
                'area' => $l[4], 'price' => $l[5], 'status' => $l[6],
            ]);
        }
    }
}
