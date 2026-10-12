<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    protected $fillable = ['name', 'region', 'province'];

    public function subtitle(): string
    {
        return collect([$this->region, $this->province === $this->region ? null : $this->province, 'Indonesia'])->filter()->implode(', ');
    }
}
