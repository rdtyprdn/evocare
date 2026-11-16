# EvoCare, Event of Care

EvoCare adalah platform SaaS berbasis **Laravel** untuk mengelola dan menemukan kegiatan pengabdian masyarakat. Cara kerjanya mirip Eventbrite, tetapi khusus kegiatan sosial seperti pendidikan, kesehatan, lingkungan, pemberdayaan ekonomi, dan tanggap bencana. Dokumen ini adalah catatan teknis yang lebih rinci. Panduan instalasi singkat ada di `README.md`.

## Fitur
- **Autentikasi** dengan Laravel Breeze dan Blade: daftar, masuk, reset kata sandi, dan profil dengan foto avatar. Email selamat datang berisi tautan verifikasi, tetapi verifikasi belum diwajibkan.
- **Tiga peran**: `admin`, `organizer` untuk penyelenggara seperti LSM, komunitas, atau institusi, dan `user` untuk masyarakat umum.
- **Beranda**: daftar kegiatan dengan pencarian kata kunci, filter kategori, kota dengan saran otomatis, dan rentang tanggal.
- **Detail kegiatan**: info penyelenggara dengan lencana terverifikasi, jadwal, lokasi, sisa kuota, gambar sampul, dan galeri dokumentasi foto serta video beserta keterangannya.
- **Pendaftaran** sebagai Relawan, Donatur, atau Peserta:
  - kuota aman dari race condition karena memakai `Cache::lock`, `DB::transaction`, dan `lockForUpdate` di `app/Services/RegistrationService.php`,
  - tidak bisa mendaftar ganda karena ada validasi dan unique index `user_id,event_id`,
  - pertanyaan pendaftaran tambahan yang bisa diatur penyelenggara per kegiatan,
  - tiket pendaftaran dengan kode unik, serta pembatalan yang mengembalikan kuota.
- **Pendaftaran Saya**: riwayat dan status pendaftaran pengguna.
- **Dashboard Penyelenggara**: kelola kegiatan milik organisasi sendiri, jadwal, daftar pendaftar dan ekspor CSV, serta laporan ringkas setelah kegiatan selesai.
- **Unggah media**: gambar sampul maksimal 10 MB, galeri hingga 30 item per kegiatan dengan foto maksimal 10 MB dan video maksimal 30 MB. File di storage ikut terhapus saat kegiatan atau akun dihapus.
- **Admin**: verifikasi organisasi, kelola kategori isu dan jenis organisasi, serta akses ke semua kegiatan termasuk moderasi galeri.
- **Keamanan**: hak akses per kegiatan diatur `EventPolicy`, ekspor CSV aman dari formula injection, dan pendaftaran akun serta permintaan reset kata sandi dibatasi 5 kali per menit per IP.

## Persyaratan
PHP 8.4 atau lebih baru dengan ekstensi pdo_mysql dan pdo_sqlite, Composer, serta Node.js dan npm. Versi paket di `composer.lock` membutuhkan PHP 8.4.1 ke atas.

## Instalasi (MAMP / MySQL)
1. Jalankan **MAMP** (Apache port 8888, MySQL port **8889**, user `root` / password `root`).
2. Buat database `evocare` (sekali saja), lewat phpMyAdmin di **http://localhost:8888/phpMyAdmin5/** atau terminal:
   ```bash
   /Applications/MAMP/Library/bin/mysql80/bin/mysql -h127.0.0.1 -P8889 -uroot -proot \
     -e "CREATE DATABASE IF NOT EXISTS evocare CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   ```
3. Siapkan aplikasi:
   ```bash
   composer install
   cp .env.example .env        # lewati jika .env sudah ada
   php artisan key:generate
   php artisan config:clear
   php artisan migrate --seed  # HANYA untuk database yang masih kosong
   php artisan storage:link
   npm install && npm run build
   php artisan serve
   ```
   Buka http://127.0.0.1:8000

Konfigurasi database di `.env` untuk MAMP. Nilai bawaan `.env.example` adalah port 3306, jadi ubah ke 8889 kalau memakai MAMP:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=8889
DB_DATABASE=evocare
DB_USERNAME=root
DB_PASSWORD=root
```

> **PERINGATAN:** jangan jalankan `php artisan migrate:fresh`, `migrate:refresh`, `migrate:reset`, atau `db:wipe` pada database MySQL ini. Perintah tersebut **menghapus semua data** (termasuk akun yang sudah dibuat). Untuk perubahan struktur cukup buat migration baru lalu `php artisan migrate`.
>
> File lama `database/database.sqlite` disimpan sebagai cadangan dan tidak dipakai lagi.

## Alur Organisasi Penyelenggara
1. Pengguna biasa (relawan/donatur/peserta) tidak perlu organisasi.
2. Siapa pun bisa klik **Daftar sebagai Penyelenggara** dan mengisi data organisasi (nama, jenis, kota, deskripsi, kontak, logo).
3. Status organisasi: *Menunggu verifikasi* → *Terverifikasi* atau *Ditolak* (dengan alasan; bisa diperbaiki dan diajukan ulang).
4. Admin memverifikasi di menu **Verifikasi Organisasi** (`/admin/organisasi`). Setelah diverifikasi, pemilik otomatis menjadi `organizer`.
5. Hanya pengguna dengan organisasi terverifikasi yang bisa membuat/mempublikasikan kegiatan, dan kegiatan selalu memakai organisasi milik pembuatnya.

Perintah perawatan data: `php artisan evocare:detach-org email@contoh.com [--dry-run]` melepas akun dari organisasi demo tanpa memindahkan kegiatannya ke organisasi lain (kegiatan otomatis terhubung ke organisasi milik akun itu setelah diverifikasi).

## Akun Demo

Seeder demo membuat akun berikut dengan kata sandi yang sama, yaitu `password`.

> **PERINGATAN:** jangan jalankan seeder demo (`php artisan db:seed` atau `migrate --seed`) di server yang bisa diakses publik. Siapa pun yang membaca README ini bisa masuk sebagai admin. Di server produksi, buat akun admin sendiri dengan kata sandi kuat, lalu pastikan `APP_ENV=production` dan `APP_DEBUG=false`.

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
Test selalu memakai SQLite in-memory (`phpunit.xml`, dengan `force="true"`), sehingga **tidak menyentuh database MySQL**. `tests/TestCase.php` juga menghentikan test jika koneksi bukan SQLite `:memory:`.
Tes mencakup autentikasi, otorisasi peran dan policy, pencarian dan filter, pendaftaran, CRUD kegiatan, unggah media, pembersihan file, ekspor CSV, pembatasan percobaan, dan seeder. Tes yang sama dijalankan otomatis oleh GitHub Actions setiap ada push atau pull request ke `main` lewat `.github/workflows/tests.yml`.

## Struktur Penting
- `app/Models`: User, Organization, OrganizationType, Category, City, Event, EventPhoto, EventQuestion, Registration, RegistrationAnswer
- `app/Services`: RegistrationService untuk pendaftaran anti race condition, EventMediaService untuk aturan unggah media, EventQuestionService untuk pertanyaan pendaftaran
- `app/Http/Controllers`: EventController publik, RegistrationController, Organizer/EventController, Organizer/EventPhotoController, OrganizationController, dan controller admin
- `app/Policies`: EventPolicy dan RegistrationPolicy
- `app/Http/Middleware/EnsureRole.php`: middleware `role:...`
- `app/Console/Commands`: `evocare:detach-org` dan `evocare:prune-files` untuk membersihkan file yang tidak lagi dipakai
- `resources/views`: tampilan Blade dan Tailwind dalam Bahasa Indonesia

## Pengembangan Berikutnya
- Hosting tetap dengan domain, HTTPS, dan pengiriman email sungguhan. Saat ini email masih dicatat ke `storage/logs/laravel.log`.
- Multi-tenant penuh: subdomain per organisasi dan paket langganan.
- REST API dengan Laravel Sanctum agar data bisa dipakai aplikasi mobile.
- Pencarian berbasis peta dan jarak, serta rekomendasi sesuai minat.
- Notifikasi pengingat lewat email atau WhatsApp, e-sertifikat relawan, laporan transparansi donasi, dan payment gateway untuk donasi.
- Daftar tunggu saat kuota penuh dan presensi dengan memindai kode QR tiket.
