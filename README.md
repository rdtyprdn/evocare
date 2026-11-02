# EvoCare

EvoCare adalah platform SaaS untuk kegiatan pengabdian masyarakat, dibuat dengan Laravel. Proyek RPL Kelompok 2, Universitas Udayana.

## Kebutuhan

- PHP 8.3 atau lebih baru
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

   - MAMP (Mac): `DB_PORT=8889`, `DB_USERNAME=root`, `DB_PASSWORD=root`. Ini sudah jadi pengaturan bawaan.
   - XAMPP (Windows): `DB_PORT=3306`, `DB_USERNAME=root`, `DB_PASSWORD=` dikosongkan.

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
- Email belum benar-benar dikirim. Isinya dicatat di `storage/logs/laravel.log`.
- Jangan commit file `.env` ke repo.
- Untuk menjalankan tes: `php artisan test`.
