<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'type', 'description', 'city'];

    public const TYPES = ['lsm' => 'Lembaga Swadaya Masyarakat', 'komunitas' => 'Komunitas', 'institusi' => 'Institusi Pendidikan'];

    public function events()
    {
        return $this->hasMany(Event::class);
    }

    public function members()
    {
        return $this->hasMany(User::class);
    }
}
