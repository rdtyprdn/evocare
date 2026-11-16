<?php

namespace App\Notifications;

use App\Models\Registration;
use App\Notifications\Concerns\QueuedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Pemberitahuan setelah pengguna membatalkan pendaftaran kegiatan. */
class RegistrationCancelled extends Notification implements ShouldQueue
{
    use QueuedMail;

    public function __construct(public Registration $registration) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $event = $this->registration->event;

        return (new MailMessage)
            ->subject('Pendaftaran kegiatan dibatalkan: '.$event->title)
            ->greeting('Halo, '.$notifiable->name.'!')
            ->line('Pendaftaran Anda untuk kegiatan "'.$event->title.'" sudah dibatalkan.')
            ->line('Jika berubah pikiran, Anda bisa mendaftar lagi selama kuota masih tersedia dan kegiatan belum berakhir.')
            ->action('Lihat Kegiatan', route('events.show', $event))
            ->salutation('Salam hangat, Tim EvoCare');
    }
}
