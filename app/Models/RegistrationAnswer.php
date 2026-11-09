<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegistrationAnswer extends Model
{
    protected $fillable = ['registration_id', 'event_question_id', 'label', 'type', 'value', 'sort_order'];

    protected function casts(): array
    {
        return ['sort_order' => 'integer'];
    }

    public function registration()
    {
        return $this->belongsTo(Registration::class);
    }

    public function question()
    {
        return $this->belongsTo(EventQuestion::class, 'event_question_id');
    }
}
