<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Pendaftaran aman dari race condition:
 * 1) Cache::lock atomik per event (mencegah dua request memproses slot yang sama bersamaan)
 * 2) DB transaction + lockForUpdate pada baris event (pengaman tingkat database, mis. MySQL/PostgreSQL)
 * 3) Unique index (user_id, event_id) mencegah pendaftaran ganda.
 */
class RegistrationService
{
    public function register(User $user, Event $event, string $type, ?string $note = null): Registration
    {
        return Cache::lock("event-register:{$event->id}", 10)->block(5, function () use ($user, $event, $type, $note) {
            return DB::transaction(function () use ($user, $event, $type, $note) {
                $locked = Event::whereKey($event->id)->lockForUpdate()->firstOrFail();

                if ($locked->status !== 'published') {
                    throw new RuntimeException('Kegiatan ini tidak menerima pendaftaran.');
                }
                if ($locked->end_at->isPast()) {
                    throw new RuntimeException('Kegiatan ini sudah berakhir.');
                }

                $existing = Registration::where('user_id', $user->id)->where('event_id', $locked->id)->lockForUpdate()->first();
                if ($existing && $existing->status === 'confirmed') {
                    throw new RuntimeException('Anda sudah terdaftar pada kegiatan ini.');
                }

                $used = Registration::where('event_id', $locked->id)->where('status', 'confirmed')->count();
                if ($used >= $locked->quota) {
                    throw new RuntimeException('Maaf, kuota kegiatan ini sudah penuh.');
                }

                if ($existing) {
                    $existing->update(['status' => 'confirmed', 'type' => $type, 'note' => $note]);

                    return $existing;
                }

                return Registration::create([
                    'user_id' => $user->id, 'event_id' => $locked->id,
                    'type' => $type, 'status' => 'confirmed', 'note' => $note,
                ]);
            });
        });
    }
}
