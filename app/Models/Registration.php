<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Registration extends Model
{
    use HasFactory;

    public const TYPES = ['relawan' => 'Relawan', 'donatur' => 'Donatur', 'peserta' => 'Peserta'];

    protected $fillable = ['user_id', 'event_id', 'type', 'status', 'note'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
