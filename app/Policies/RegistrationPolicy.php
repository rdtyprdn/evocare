<?php

namespace App\Policies;

use App\Models\Registration;
use App\Models\User;

class RegistrationPolicy
{
    public function cancel(User $user, Registration $registration): bool
    {
        return $registration->user_id === $user->id;
    }

    public function viewTicket(User $user, Registration $registration): bool
    {
        return $registration->user_id === $user->id && $registration->status === 'confirmed';
    }
}

