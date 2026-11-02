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
        return $user->isAdmin() || $this->owns($user, $event);
    }

    /**
     * Pemilik = pembuat kegiatan, dan kegiatan itu milik organisasi pengguna saat ini.
     * Kegiatan tanpa organisasi (pengguna dilepas dari organisasi demo) tetap bisa dikelola pembuatnya.
     */
    private function owns(User $user, Event $event): bool
    {
        if ((int) $event->user_id !== (int) $user->id) {
            return false;
        }

        return $event->organization_id === null
            || (int) $event->organization_id === (int) $user->organization_id;
    }

    public function delete(User $user, Event $event): bool
    {
        return $this->update($user, $event);
    }

    public function viewRegistrants(User $user, Event $event): bool
    {
        return $this->update($user, $event);
    }

    /** Galeri dokumentasi hanya dikelola oleh penyelenggara pemilik kegiatan. */
    public function managePhotos(User $user, Event $event): bool
    {
        return $this->owns($user, $event);
    }
}
