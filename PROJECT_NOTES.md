# Catatan Proyek — Aterra CRM (Omnichannel)

Status & catatan operasional per 2026-06-21. Pendamping [DEPLOYMENT.md](DEPLOYMENT.md).

---

## ✅ Sudah jalan
- **WhatsApp** (Wablas/Fonnte) — full publik, AI auto-reply, status terkirim/dibaca.
- **Messenger** — DM masuk + AI balas.
- **Instagram** — DM + komentar (auto-reply + private DM).
- **Facebook** — komentar postingan/iklan → balas publik + **private DM ke pengomentar → lead di CRM** (dua arah).
- **Config di DB** (`settings`) + halaman **/configuration** (tab AI / WhatsApp / Meta). Ubah setting → langsung berlaku (tanpa `config:clear`).
- **Pause global AI** — toggle "AI Aktif / Dijeda" di header inbox (mematikan semua auto-reply; pesan tetap tercatat).
- **Auto-refresh token IG** — command `meta:refresh-ig-token` (cron harian 03:00).
- **Receipts** delivered/read untuk WA (Wablas tracking) & Meta.
- **AI Post Composer** (`/compose`, menu "Buat Postingan") — TERPISAH dari chat: prompt → AI caption (`PostWriter`) → upload gambar → publish ke FB Page & IG (`ContentPublisher`), riwayat di tabel `posts`, konfirmasi via modal. **FB + IG posting JALAN.** FB: `POST /me/feed` atau `/me/photos` (page token). IG: pakai **page token** (graph.facebook.com/{ig-id}/media → media_publish), izin `instagram_content_publish` + `instagram_basic`, **wajib gambar**, dan **poll status_code FINISHED** dulu sebelum publish (kalau langsung publish → error 9007 "media belum siap").

## ⏳ TODO (prioritas)
1. ✅ **SELESAI — Page token FB PERMANEN.** Dibuat via `php artisan meta:setup-page-token "USER_TOKEN" --page=1066085583255460` (output `expires_at: 0`). Token ini permanen & lengkap: FB DM/komentar/posting + IG posting. **Cara ulang bila perlu** (mis. tambah izin): tambah izin di Graph API Explorer → ambil **USER token** (kolom "Token Akses", bukan dari JSON) → jalankan command → otomatis tersimpan ke settings (tak perlu update /configuration manual).
2. **App Review + Business Verification** — untuk DM/komentar ke **publik** (advanced access). Sekarang Standard Access = terbatas tester. Untuk produk melayani Page klien → perlu jadi "Tech Provider".
3. **Auth/login untuk /configuration** — halaman ini menyimpan secret tapi **masih terbuka tanpa login**. Lindungi sebelum dipakai serius.
4. (Opsional) `META_APP_SECRET` & lainnya: aman; IG token kedaluwarsa ~60 hari (auto-refresh sudah ada bila cron jalan & IG aktif).

---

## 🔑 ID penting
- App: **AI Omnichannel**, App ID `27377340705227630`
- FB Page: **Inspirasi Fajar**, id `1066085583255460`
- IG: **@nugrohofajar88**, IGSID `17841408078739769`
- Webhook: `https://chatbot.fajarnugroho.info/api/webhooks/meta` · `/api/webhooks/wablas` (+ `/wablas/tracking`)

## 🎛️ Toggle (di /configuration, kecuali pause)
- **Pause global**: tombol di inbox (setting `ai_paused`).
- `META_MESSENGER_ENABLED` / `META_INSTAGRAM_ENABLED` — on/off channel (DM).
- `META_MESSENGER_COMMENTS_ENABLED` / `META_INSTAGRAM_COMMENTS_ENABLED` — tangani komentar.
- `META_MESSENGER_COMMENT_PUBLIC_REPLY` — balas komentar FB publik (butuh `pages_manage_engagement`).

## ⌨️ Command artisan
- `php artisan meta:setup-page-token "USER_TOKEN" --page=1066085583255460` — buat Page token **PERMANEN** dari user token & simpan ke settings.
- `php artisan meta:subscribe-page` — subscribe FB Page ke field webhook (`feed,messages,...`) pakai Page token aktif.
- `php artisan meta:refresh-ig-token` — refresh token IG (dijadwalkan harian).

---

## 🧠 Pelajaran token Meta (penting saat error)
- **Page token ≠ User token.** Cek: GET `me?fields=id,name` → harus muncul **nama Page** (id `1066085583255460`), bukan nama pribadi. Kalau muncul nama pribadi = itu User token (salah) → error `me does not exist` saat kirim.
- **Ambil Page token:** Graph API Explorer → GET `me/accounts?fields=name,access_token` → copy `access_token` dari **dalam respons** (bukan kolom token atas).
- **Izin FB komentar:** `pages_messaging`, `pages_read_engagement`, `pages_manage_metadata`, `pages_manage_engagement`, `pages_read_user_content`.
- **Subscribe feed wajib di level Page** (`subscribed_apps`), bukan cuma toggle field app-level. Pakai `meta:subscribe-page`.
- **Private reply komentar = Send API**: `POST /me/messages` dengan `recipient: {comment_id}` (BUKAN `/{comment-id}/private_replies`).
- **Jangan ikut-copy komentar `# ...` di `.env`** saat menyalin token (bikin "Cannot parse access token").
- Webhook IG/FB ditandatangani dengan **secret berbeda**: Messenger = App Secret FB (`META_APP_SECRET`); Instagram = `META_IG_APP_SECRET`. `verifySignature` coba keduanya.

## 🚀 Deploy singkat
```bash
git pull
php artisan migrate --force
php artisan config:clear
php artisan db:seed --class=SettingsSeeder
php artisan view:clear && php artisan cache:clear
```
Cron (sekali, di cPanel): `* * * * * /usr/local/bin/ea-php84 /home/fajh6696/public_html/chatbot.fajarnugroho.info/artisan schedule:run >> /dev/null 2>&1`
