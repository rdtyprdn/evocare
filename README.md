# EvoCare – Event of Care

EvoCare adalah platform (MVP) berbasis **Laravel** untuk mengelola dan menemukan kegiatan pengabdian masyarakat, mirip Eventbrite tapi khusus kegiatan sosial: pendidikan, kesehatan, lingkungan, pemberdayaan ekonomi, dan darurat bencana.

## Fitur MVP
- **Autentikasi** (Laravel Breeze, Blade): daftar, masuk, reset kata sandi, profil.
- **3 peran**: `admin`, `organizer` (penyelenggara: LSM / komunitas / institusi), `user` (masyarakat).
- **Beranda ala Eventbrite**: daftar kegiatan mendatang + pencarian kata kunci, filter **kategori**, **kota**, dan **rentang tanggal**.
- **Detail kegiatan**: info penyelenggara, jadwal, lokasi, sisa kuota.
- **Pendaftaran terpusat** sebagai Relawan / Donatur / Peserta, dengan:
  - kuota **aman dari race condition** (`Cache::lock` atomik + `DB::transaction` + `lockForUpdate`) – lihat `app/Services/RegistrationService.php`,
  - tidak bisa daftar ganda (validasi + unique index `user_id,event_id`),
  - pembatalan pendaftaran (kuota kembali tersedia).
- **Pendaftaran Saya**: riwayat dan status pendaftaran pengguna.
- **Dashboard Penyelenggara**: CRUD kegiatan milik sendiri (dilindungi Policy), lihat daftar pendaftar.
- **Admin**: kelola kategori isu, serta akses ke semua kegiatan.

## Persyaratan
PHP ≥ 8.3 (dengan ekstensi pdo_sqlite), Composer, Node.js + npm.

## Instalasi
```bash
composer install
cp .env.example .env        # lewati jika .env sudah ada
php artisan key:generate
touch database/database.sqlite
php artisan migrate:fresh --seed
npm install && npm run build
php artisan serve
```
Buka http://127.0.0.1:8000

## Akun Demo (kata sandi semua: `password`)
| Peran | Email |
|---|---|
| Admin | admin@evocare.test |
| Penyelenggara (Yayasan Peduli Pesisir, Makassar) | organizer@evocare.test |
| Penyelenggara (Komunitas Berbagi Ilmu, Bandung) | organizer2@evocare.test |
| Penyelenggara (BEM Universitas Nusantara, Yogyakarta) | organizer3@evocare.test |
| Pengguna | user@evocare.test, dewi@evocare.test, rizky@evocare.test |

Tips demo kuota: kegiatan **"Posko Tanggap Banjir"** hanya berkuota 3 dan sudah terisi 2.

## Pengujian
```bash
php artisan test
```
Mencakup: autentikasi (Breeze), otorisasi peran & policy, pencarian & filter, pendaftaran (kuota, duplikat, pembatalan), CRUD kegiatan, dan seeder. Test khusus EvoCare ada di `tests/Feature/EvoCareTest.php`.

## Struktur Penting
- `app/Models` – User, Organization, Category, Event, Registration
- `app/Services/RegistrationService.php` – logika pendaftaran anti race condition
- `app/Http/Controllers` – EventController (publik), RegistrationController, Organizer/EventController, Admin/CategoryController
- `app/Policies` – EventPolicy, RegistrationPolicy
- `app/Http/Middleware/EnsureRole.php` – middleware `role:...`
- `resources/views` – tampilan Blade + Tailwind (Bahasa Indonesia)

## Pengembangan Berikutnya
- **Multi-tenant SaaS**: isolasi data per organisasi (tenant), subdomain per organisasi, paket langganan.
- **Headless CMS**: REST/GraphQL API (Laravel Sanctum) agar konten bisa dipakai aplikasi mobile/web lain.
- Pencarian berbasis lokasi (peta, radius jarak) & rekomendasi sesuai minat.
- Unggah gambar, verifikasi organisasi, notifikasi email/WhatsApp, e-sertifikat relawan, laporan transparansi donasi, dan payment gateway untuk donasi.
- Daftar tunggu (waitlist) saat kuota penuh, presensi via QR code.
