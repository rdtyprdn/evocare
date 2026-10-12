<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    public function create(User $user): bool
    {
        // Hanya pemilik/anggota organisasi yang sudah terverifikasi yang boleh membuat kegiatan.
        return $user->hasVerifiedOrganization();
    }

    public function update(User $user, Event $event): bool
    {
        return $user->isAdmin() || $event->user_id === $user->id;
    }

    public function delete(User $user, Event $event): bool
    {
        return $this->update($user, $event);
    }

    public function viewRegistrants(User $user, Event $event): bool
    {
        return $this->update($user, $event);
    }
}
