<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class EventQuestion extends Model
{
    public const TYPES = [
        'text' => 'Teks singkat',
        'textarea' => 'Teks panjang',
        'number' => 'Angka',
        'select' => 'Pilihan (dropdown)',
        'date' => 'Tanggal',
        'boolean' => 'Ya / Tidak',
    ];

    public const MAX_PER_EVENT = 20;

    public const MAX_OPTIONS = 30;

    protected $fillable = ['event_id', 'label', 'type', 'options', 'required', 'roles', 'sort_order'];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'roles' => 'array',
            'required' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function answers()
    {
        return $this->hasMany(RegistrationAnswer::class);
    }

    public function appliesTo(string $role): bool
    {
        return in_array($role, $this->roles ?? [], true);
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    /** Aturan validasi jawaban untuk pertanyaan ini. */
    public function answerRules(): array
    {
        $rules = [$this->required ? 'required' : 'nullable'];

        return array_merge($rules, match ($this->type) {
            'textarea' => ['string', 'max:2000'],
            'number' => ['numeric', 'min:0', 'max:1000000000'],
            'select' => ['string', Rule::in($this->options ?? [])],
            'date' => ['date'],
            'boolean' => ['in:ya,tidak'],
            default => ['string', 'max:255'],
        });
    }

    /** Ubah jawaban mentah menjadi teks yang disimpan. */
    public function formatAnswer(mixed $value): ?string
    {
        if ($value === null || (is_string($value) && trim($value) === '')) {
            return null;
        }
        $value = trim((string) $value);

        return match ($this->type) {
            'boolean' => $value === 'ya' ? 'Ya' : 'Tidak',
            default => $value,
        };
    }

    /**
     * Pertanyaan rekomendasi berdasarkan slug kategori. Kunci "donatur" berlaku untuk semua kategori.
     * Penyelenggara tetap bisa mengubah atau menghapusnya sebelum menyimpan.
     */
    public static function presets(): array
    {
        $kaos = ['S', 'M', 'L', 'XL', 'XXL'];

        return [
            'kesehatan' => [
                ['label' => 'Golongan darah', 'type' => 'select', 'options' => ['A', 'B', 'AB', 'O', 'Belum tahu'], 'required' => true, 'roles' => ['relawan', 'peserta']],
                ['label' => 'Rhesus darah', 'type' => 'select', 'options' => ['Positif (+)', 'Negatif (-)', 'Belum tahu'], 'required' => false, 'roles' => ['relawan', 'peserta']],
                ['label' => 'Berat badan (kg)', 'type' => 'number', 'options' => [], 'required' => true, 'roles' => ['relawan', 'peserta']],
                ['label' => 'Riwayat penyakit atau obat yang sedang dikonsumsi', 'type' => 'textarea', 'options' => [], 'required' => false, 'roles' => ['relawan', 'peserta']],
            ],
            'darurat-bencana' => [
                ['label' => 'Keahlian yang dimiliki', 'type' => 'select', 'options' => ['Medis / P3K', 'Evakuasi dan SAR', 'Dapur umum', 'Logistik', 'Pendampingan psikososial', 'Lainnya'], 'required' => true, 'roles' => ['relawan']],
                ['label' => 'Kontak darurat (nama dan nomor HP)', 'type' => 'text', 'options' => [], 'required' => true, 'roles' => ['relawan']],
                ['label' => 'Ukuran kaos', 'type' => 'select', 'options' => $kaos, 'required' => false, 'roles' => ['relawan']],
            ],
            'pendidikan' => [
                ['label' => 'Bidang yang dikuasai', 'type' => 'select', 'options' => ['Matematika', 'Bahasa Indonesia', 'Bahasa Inggris', 'IPA', 'IPS', 'Komputer', 'Seni dan Budaya', 'Lainnya'], 'required' => true, 'roles' => ['relawan']],
                ['label' => 'Jenjang pendidikan', 'type' => 'select', 'options' => ['SD', 'SMP', 'SMA / SMK', 'Perguruan Tinggi', 'Umum'], 'required' => false, 'roles' => ['peserta']],
            ],
            'lingkungan' => [
                ['label' => 'Ukuran kaos', 'type' => 'select', 'options' => $kaos, 'required' => false, 'roles' => ['relawan', 'peserta']],
                ['label' => 'Membawa alat kebersihan sendiri', 'type' => 'boolean', 'options' => [], 'required' => false, 'roles' => ['relawan', 'peserta']],
            ],
            'pemberdayaan-ekonomi' => [
                ['label' => 'Keahlian atau bidang usaha', 'type' => 'text', 'options' => [], 'required' => true, 'roles' => ['relawan', 'peserta']],
            ],
            'donatur' => [
                ['label' => 'Jenis donasi', 'type' => 'select', 'options' => ['Uang', 'Barang', 'Makanan / minuman', 'Lainnya'], 'required' => true, 'roles' => ['donatur']],
                ['label' => 'Rencana nominal atau jumlah donasi', 'type' => 'text', 'options' => [], 'required' => false, 'roles' => ['donatur']],
            ],
        ];
    }
}
