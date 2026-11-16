<?php

namespace App\Notifications;

use App\Models\Registration;
use App\Notifications\Concerns\QueuedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Konfirmasi setelah pengguna berhasil mendaftar kegiatan. */
class RegistrationConfirmed extends Notification implements ShouldQueue
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
        $type = Registration::TYPES[$this->registration->type] ?? $this->registration->type;

        return (new MailMessage)
            ->subject('Pendaftaran kegiatan berhasil: '.$event->title)
            ->greeting('Halo, '.$notifiable->name.'!')
            ->line('Pendaftaran Anda sebagai '.mb_strtolower($type).' untuk kegiatan "'.$event->title.'" sudah kami terima.')
            ->line('Waktu: '.$event->start_at->translatedFormat('l, d F Y, H:i'))
            ->line('Lokasi: '.($event->location ?: '-'))
            ->action('Lihat Tiket', route('registrations.ticket', $this->registration))
            ->line('Tiket juga bisa dibuka kapan saja dari menu Pendaftaran Saya di dashboard.')
            ->salutation('Salam hangat, Tim EvoCare');
    }
}
