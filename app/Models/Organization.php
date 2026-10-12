<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    use HasFactory;

    protected $fillable = ['owner_id', 'name', 'slug', 'type', 'description', 'city', 'logo_path', 'contact', 'status', 'admin_note', 'verified_at'];

    public const TYPES = ['lsm' => 'Lembaga Swadaya Masyarakat', 'komunitas' => 'Komunitas', 'institusi' => 'Institusi Pendidikan'];

    public const STATUSES = ['pending' => 'Menunggu verifikasi', 'verified' => 'Terverifikasi', 'rejected' => 'Ditolak'];

    protected function casts(): array
    {
        return ['verified_at' => 'datetime'];
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function events()
    {
        return $this->hasMany(Event::class);
    }

    public function members()
    {
        return $this->hasMany(User::class);
    }

    public function isVerified(): bool
    {
        return $this->status === 'verified';
    }

    public function logoUrl(): ?string
    {
        return $this->logo_path ? asset('storage/'.$this->logo_path) : null;
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }
}
