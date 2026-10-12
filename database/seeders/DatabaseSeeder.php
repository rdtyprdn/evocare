<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $cats = collect([
            ['Pendidikan', null], ['Kesehatan', null], ['Lingkungan', null], ['Pemberdayaan Ekonomi', null], ['Darurat Bencana', null],
        ])->mapWithKeys(fn ($c) => [Str::slug($c[0]) => Category::create(['name' => $c[0], 'slug' => Str::slug($c[0]), 'icon' => $c[1]])]);

        User::create(['name' => 'Admin EvoCare', 'email' => 'admin@evocare.test', 'password' => 'password', 'role' => 'admin', 'email_verified_at' => now()]);

        $orgs = [
            ['Yayasan Peduli Pesisir', 'lsm', 'Makassar', 'organizer@evocare.test', 'Andi Pratama'],
            ['Komunitas Berbagi Ilmu', 'komunitas', 'Bandung', 'organizer2@evocare.test', 'Siti Rahmawati'],
            ['BEM Universitas Nusantara', 'institusi', 'Yogyakarta', 'organizer3@evocare.test', 'Budi Santoso'],
        ];
        $organizers = [];
        foreach ($orgs as [$name, $type, $city, $email, $person]) {
            $org = Organization::create(['name' => $name, 'slug' => Str::slug($name), 'type' => $type, 'city' => $city, 'description' => "$name bergerak di bidang pengabdian masyarakat di $city."]);
            $organizers[] = User::create(['name' => $person, 'email' => $email, 'password' => 'password', 'role' => 'organizer', 'organization_id' => $org->id, 'email_verified_at' => now()]);
        }

        $users = collect([['Raditya Pradana', 'user@evocare.test'], ['Dewi Lestari', 'dewi@evocare.test'], ['Rizky Hidayat', 'rizky@evocare.test']])
            ->map(fn ($u) => User::create(['name' => $u[0], 'email' => $u[1], 'password' => 'password', 'role' => 'user', 'email_verified_at' => now()]));

        $events = [
            [0, 'lingkungan', 'Bersih Pantai Losari', 'Makassar', 'Pantai Losari', 5, 40, 'Aksi bersih pantai bersama warga dan relawan. Disediakan sarung tangan, karung sampah, dan konsumsi. Sampah plastik akan dipilah untuk didaur ulang.'],
            [0, 'lingkungan', 'Penanaman 1.000 Mangrove', 'Makassar', 'Pesisir Untia', 12, 60, 'Mari tanam mangrove untuk mencegah abrasi dan menjaga ekosistem pesisir.'],
            [0, 'darurat-bencana', 'Posko Tanggap Banjir: Pengemasan Logistik', 'Makassar', 'Gudang Yayasan Peduli Pesisir', 2, 3, 'Membutuhkan relawan untuk mengemas paket sembako bagi korban banjir. Donatur juga dipersilakan menyalurkan bantuan.'],
            [1, 'pendidikan', 'Kelas Membaca Ceria untuk Anak', 'Bandung', 'Taman Baca Cibaduyut', 7, 20, 'Mengajar membaca dan mendongeng untuk anak usia 6–10 tahun. Cocok untuk mahasiswa dan guru.'],
            [1, 'pendidikan', 'Workshop Literasi Digital Lansia', 'Bandung', 'Aula Kelurahan Coblong', 15, 30, 'Mengajarkan penggunaan smartphone, mengenali hoaks, dan keamanan transaksi digital bagi lansia.'],
            [1, 'pemberdayaan-ekonomi', 'Pelatihan Foto Produk UMKM', 'Bandung', 'Co-working Dago', 20, 25, 'Pelatihan memotret produk dengan smartphone dan berjualan online untuk pelaku UMKM.'],
            [2, 'kesehatan', 'Donor Darah Kampus', 'Yogyakarta', 'Gedung Pusat Universitas Nusantara', 4, 100, 'Donor darah bekerja sama dengan PMI. Peserta wajib sehat, cukup tidur, dan sudah sarapan.'],
            [2, 'kesehatan', 'Cek Kesehatan Gratis Warga Desa', 'Yogyakarta', 'Balai Desa Sleman', 10, 50, 'Pemeriksaan tensi, gula darah, dan konsultasi gizi. Dibutuhkan relawan tenaga kesehatan dan non-kesehatan.'],
            [2, 'pemberdayaan-ekonomi', 'Bazar Produk Desa Binaan', 'Yogyakarta', 'Alun-alun Utara', 25, 80, 'Bazar produk olahan desa binaan. Relawan membantu kasir, logistik, dan promosi.'],
            [2, 'pendidikan', 'Mengajar di Sekolah Pelosok', 'Yogyakarta', 'SD Negeri Gunungkidul 3', 30, 15, 'Program mengajar sehari di sekolah dasar pelosok: matematika, bahasa Inggris, dan sains sederhana.'],
        ];

        foreach ($events as $i => [$o, $cat, $title, $city, $loc, $days, $quota, $desc]) {
            $start = now()->addDays($days)->setTime(8, 0);
            $organizer = $organizers[$o];
            $event = Event::create([
                'organization_id' => $organizer->organization_id, 'category_id' => $cats[$cat]->id, 'user_id' => $organizer->id,
                'title' => $title, 'slug' => Str::slug($title), 'description' => $desc, 'city' => $city, 'location' => $loc,
                'start_at' => $start, 'end_at' => (clone $start)->addHours(5), 'quota' => $quota,
                'image_url' => 'https://picsum.photos/seed/evocare'.($i + 1).'/800/400', 'status' => 'published',
            ]);
            if ($i % 2 === 0) {
                Registration::create(['user_id' => $users[1]->id, 'event_id' => $event->id, 'type' => 'relawan']);
            }
        }

        // Kegiatan hampir penuh untuk demo kuota
        $flood = Event::where('slug', 'posko-tanggap-banjir-pengemasan-logistik')->first();
        Registration::create(['user_id' => $users[2]->id, 'event_id' => $flood->id, 'type' => 'donatur']);
    }
}
