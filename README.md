# EvoCare

EvoCare adalah platform SaaS untuk kegiatan pengabdian masyarakat, dibuat dengan Laravel. Proyek RPL Kelompok 2, Universitas Udayana.

## Kebutuhan

- PHP 8.4 atau lebih baru
- Composer
- Node.js dan npm
- Git
- MySQL. Di Mac bisa pakai MAMP, di Windows bisa pakai XAMPP atau Laragon.

## Cara install

1. Clone repo lalu install dependensi:

   ```bash
   git clone https://github.com/rdtyprdn/evocare.git
   cd evocare
   composer install
   npm install
   ```

2. Salin file konfigurasi dan buat kunci aplikasi:

   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

   Di Windows CMD, ganti `cp` dengan `copy`.

3. Nyalakan MySQL, buka phpMyAdmin, lalu buat database kosong bernama `evocare`.

4. Sesuaikan bagian database di file `.env`:

   - MAMP (Mac): ubah ke `DB_PORT=8889`, dengan `DB_USERNAME=root` dan `DB_PASSWORD=root`.
   - XAMPP (Windows): `DB_PORT=3306` sudah jadi pengaturan bawaan, `DB_USERNAME=root`, dan `DB_PASSWORD=` dikosongkan.

5. Buat tabel, isi data awal, dan sambungkan folder upload:

   ```bash
   php artisan migrate --seed
   php artisan storage:link
   ```

   `storage:link` diperlukan supaya foto, video, dan avatar bisa tampil.

## Menjalankan aplikasi

Buka dua terminal di folder project:

```bash
npm run dev
```

```bash
php artisan serve
```

Lalu buka http://localhost:8000 di browser.

## Catatan

- Database di tiap laptop terpisah, jadi akun dan kegiatan yang dibuat di satu laptop tidak muncul di laptop lain.
- Secara bawaan (`MAIL_MAILER=log`) email tidak benar-benar dikirim. Isinya dicatat di `storage/logs/laravel.log`. Cara mengirim email sungguhan ada di bagian "Email sungguhan dengan Resend" di bawah.
- Jangan commit file `.env` ke repo.
- Untuk menjalankan tes: `php artisan test`. Tes juga jalan otomatis di GitHub Actions setiap ada push ke `main`.
- Jangan jalankan seeder demo di server publik, karena semua akun demo memakai kata sandi `password`.

## Email sungguhan dengan Resend

EvoCare mengirim email verifikasi, atur ulang kata sandi, konfirmasi dan pembatalan pendaftaran kegiatan, serta hasil verifikasi organisasi. Pengguna wajib memverifikasi email sebelum bisa mendaftar kegiatan, mengajukan organisasi, atau membuat kegiatan.

1. Buat API key di dashboard Resend, lalu isi `.env`:

   ```
   MAIL_MAILER=resend
   RESEND_API_KEY=isi_api_key_anda
   MAIL_FROM_ADDRESS="onboarding@resend.dev"
   ```

   Alamat `onboarding@resend.dev` hanya bisa mengirim ke email pemilik akun Resend. Supaya bisa mengirim ke siapa saja, verifikasi domain di Resend dan ganti `MAIL_FROM_ADDRESS` dengan alamat dari domain itu.

2. Pastikan `APP_URL` sama dengan alamat yang dibuka di browser, karena tautan di email dibuat dari nilai ini.

3. Email dikirim lewat antrian (`QUEUE_CONNECTION=database`). Jalankan worker di terminal terpisah:

   ```bash
   php artisan queue:work
   ```

   Atau jalankan `composer run dev`, yang sudah menjalankan server, Vite, dan antrian sekaligus. Kalau tidak mau menjalankan worker, set `QUEUE_CONNECTION=sync` supaya email dikirim langsung.

4. Setelah mengubah `.env`, jalankan `php artisan config:clear`. Kalau worker sedang jalan, restart dengan `php artisan queue:restart`.

Email yang gagal dikirim dicoba ulang tiga kali, lalu dicatat di tabel `failed_jobs`. Lihat dengan `php artisan queue:failed` dan kirim ulang dengan `php artisan queue:retry all`.

## Panduan lengkap Windows (laptop baru)

### A. Install aplikasi

1. [Git for Windows](https://git-scm.com/download/win), pilihan bawaan.
2. [Node.js LTS](https://nodejs.org/), pilihan bawaan.
3. [VC++ Redistributable x64](https://aka.ms/vs/17/release/vc_redist.x64.exe). Wajib agar PHP bisa jalan.
4. PHP dari [windows.php.net](https://windows.php.net/download/): pilih PHP 8.4, **VS17 x64 Thread Safe**, file Zip. Gunakan versi 8.4.27 atau lebih baru.
   - Ekstrak ke `C:\php` sehingga ada `C:\php\php.exe`.
   - Salin `php.ini-development` menjadi `php.ini`.
   - Buka `php.ini`, hapus tanda `;` di depan baris berikut:

     ```
     extension_dir = "ext"
     extension=curl
     extension=fileinfo
     extension=gd
     extension=mbstring
     extension=openssl
     extension=pdo_mysql
     extension=zip
     ```

   - Ubah `upload_max_filesize = 128M`, `post_max_size = 128M`, `memory_limit = 512M`.
   - Tambahkan `C:\php` ke Path: Start, cari "Edit the system environment variables", Environment Variables, pilih Path di System variables, Edit, New, isi `C:\php`.
5. [Composer-Setup.exe](https://getcomposer.org/Composer-Setup.exe). Saat ditanya lokasi PHP, pilih `C:\php\php.exe`.
6. [MySQL Installer](https://dev.mysql.com/downloads/installer/): pilih MySQL Server 8, port `3306`, buat password untuk `root` dan catat.

### B. Cek instalasi

Tutup semua PowerShell, buka yang baru, lalu jalankan:

```
php -v
php -m
composer -V
node -v
npm -v
git --version
```

Semua harus menampilkan versi, dan `php -m` harus memuat `pdo_mysql`, `gd`, `fileinfo`, `zip`, `mbstring`, `openssl`.

### C. Buat database

Buka **MySQL 8.0 Command Line Client**, masukkan password root, lalu:

```sql
CREATE DATABASE evocare;
```

### D. Siapkan project

```
cd $HOME\Documents
git clone https://github.com/rdtyprdn/evocare.git
cd evocare
composer install
npm install
copy .env.example .env
php artisan key:generate
```

Ubah bagian database di `.env`:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=evocare
DB_USERNAME=root
DB_PASSWORD=isi_password_mysql_kamu
```

Lalu:

```
php artisan config:clear
php artisan migrate --seed
php artisan storage:link
```

### E. Jalankan

Buka dua PowerShell di folder `evocare`. Terminal pertama `npm run dev`, terminal kedua `php artisan serve`, lalu buka http://localhost:8000. Untuk pemakaian berikutnya cukup langkah E.

### Kalau muncul error

| Pesan | Solusi |
|---|---|
| `'php' is not recognized` | Path belum benar. Cek langkah A4 dan buka PowerShell baru. |
| `could not find driver` | Aktifkan `extension=pdo_mysql` dan `extension_dir` di `php.ini`. |
| `VCRUNTIME140.dll` | Install VC++ Redistributable (langkah A3). |
| `composer install` gagal karena ekstensi | Aktifkan ekstensi yang disebut di `php.ini`. |
| `Access denied for user 'root'` | Password di `.env` salah. Perbaiki lalu `php artisan config:clear`. |
| `SQLSTATE[HY000] [2002]` | MySQL belum menyala. Nyalakan service MySQL80 di Services. |
| `storage:link` gagal | Jalankan PowerShell sebagai Administrator. |
| Tampilan tanpa gaya | Pastikan `npm run dev` berjalan, atau jalankan `npm run build`. |
| Port 8000 terpakai | `php artisan serve --port=8080` |
| `npm.ps1 cannot be loaded` | `Set-ExecutionPolicy -Scope CurrentUser RemoteSigned` |
