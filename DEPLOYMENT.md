# Panduan Deploy ke Hosting

Langkah deploy untuk rilis yang memindahkan konfigurasi ke database
(DB-config + Settings UI + auto-refresh token Instagram).

---

## 1. Push & pull kode

Pastikan **semua file ikut**, termasuk hasil build asset:

- `public/build/**` (CSS/JS terbaru — halaman `/configuration` butuh class Tailwind baru)
- file `app/`, `config/`, `database/migrations`, `database/seeders`, `routes/`, `resources/views`

```bash
git pull
```

---

## 2. Jalankan perintah berurutan di server

```bash
php artisan migrate --force                    # migration settings (key→code, + group, description)
php artisan config:clear                       # WAJIB sebelum seed (biar env() kebaca)
php artisan db:seed --class=SettingsSeeder     # salin nilai .env → tabel settings
php artisan config:cache                       # opsional
php artisan route:clear
php artisan view:clear
```

> ⚠️ **Urutan `config:clear` sebelum seed itu krusial.**
> Kalau config sedang ter-cache, `env()` mengembalikan `null` → setting akan keseed kosong.
> Jalankan `config:cache` lagi **setelah** seeding (kalau memang pakai config cache).

---

## 3. Tambah CRON (sekali saja)

Dibutuhkan agar scheduler Laravel jalan — termasuk **auto-refresh token Instagram**
(`meta:refresh-ig-token`) dan task terjadwal lain ke depan.

Cukup **SATU cron** yang berjalan **tiap menit**. Cron ini hanya memanggil `schedule:run`
sebagai "detak jantung"; Laravel sendiri yang memutuskan task mana yang jatuh tempo.
Jadi walau dipanggil tiap menit, `meta:refresh-ig-token` tetap hanya jalan **1x/hari jam 03:00**
(lihat `routes/console.php`). Menambah task terjadwal baru tidak perlu menambah cron lagi.

### Di cPanel (Cron Jobs)

- **Common Settings:** pilih **"Once Per Minute (`* * * * *`)"**.
- **Command:** panggil **binary PHP 8.4 + path lengkap ke `artisan`** (tanpa `cd`):

```
/usr/local/bin/ea-php84 /home/fajh6696/public_html/chatbot.fajarnugroho.info/artisan schedule:run >> /dev/null 2>&1
```

> Sesuaikan **username** (`fajh6696`), **path domain**, dan **versi PHP**.
> Proyek butuh **PHP ≥ 8.4** — cek versi yang ditugaskan ke domain di
> **cPanel → MultiPHP Manager**, lalu pakai binary yang cocok (mis. `ea-php84`).
> Jangan pakai `/usr/local/bin/php` polos kalau default-nya versi lama.

### Tes dulu (via Terminal/SSH cPanel)

```bash
/usr/local/bin/ea-php84 /home/fajh6696/public_html/chatbot.fajarnugroho.info/artisan schedule:list
```

Kalau muncul `meta:refresh-ig-token` dengan jadwal `0 3 * * *`, berarti path & PHP sudah benar —
cron tinggal pakai command yang sama (ganti `schedule:list` → `schedule:run`).

### (Opsional) log untuk debug

Ganti `>> /dev/null 2>&1` dengan file log:

```
... artisan schedule:run >> /home/fajh6696/public_html/chatbot.fajarnugroho.info/storage/logs/schedule.log 2>&1
```

---

## 4. Verifikasi setelah deploy

- [ ] Buka **`/configuration`** → muncul 3 grup (**AI / WhatsApp / Meta**), kolom secret **ter-mask** (hanya 4 karakter terakhir).
- [ ] Kirim pesan **WhatsApp** uji → AI masih membalas (config kini dari DB, hasilnya harus identik).
- [ ] `php artisan schedule:list` → muncul **`meta:refresh-ig-token`**.

---

## 5. Bersihkan `.env` (setelah yakin DB terisi)

`.env` **operasional** boleh dikosongkan karena sumber kebenarannya sekarang di database.

**TETAP di `.env` (jangan dihapus — infra/bootstrap):**

```
APP_KEY
APP_ENV
DB_*
CACHE_STORE
QUEUE_CONNECTION
```

Sisanya (`AI_*`, `GEMINI_*`, `OPENAI_*`, `OPENROUTER_*`, `WHATSAPP_DRIVER`,
`FONNTE_*`, `WABLAS_*`, `META_*`) sumbernya **DB** dan diatur lewat halaman `/configuration`.

---

## Catatan

- **Mengubah setting tidak perlu `config:clear`.** Perubahan lewat `/configuration`
  (atau `Setting::put`) langsung berlaku — cache settings otomatis dibuang.
  `config:clear` hanya dibutuhkan saat mengubah **`.env`** (infra).
- **Keamanan:** halaman `/configuration` menyimpan secret dan saat ini **belum dilindungi login**.
  Lindungi dengan autentikasi sebelum diakses publik.
- **Token Instagram** berumur ~60 hari; command `meta:refresh-ig-token` me-refresh otomatis
  ~10 hari sebelum kedaluwarsa (hanya jika Instagram aktif & CRON berjalan).
