<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    public function create(User $user): bool
    {
        return in_array($user->role, ['organizer', 'admin'], true);
    }

    public function update(User $user, Event $event): bool
    {
        return $user->isAdmin() || ($user->isOrganizer() && $event->user_id === $user->id);
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
