<?php

namespace App\Notifications;

use App\Notifications\Concerns\QueuedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Email selamat datang untuk akun baru. */
class WelcomeEmail extends Notification implements ShouldQueue
{
    use QueuedMail;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Selamat datang di EvoCare')
            ->greeting('Halo, '.$notifiable->name.'!')
            ->line('Terima kasih sudah membuat akun di EvoCare. Sekarang Anda bisa menjelajahi kegiatan sosial di sekitar Anda, mendaftar kegiatan, atau mengajukan organisasi.')
            ->line('Jika Anda tidak merasa membuat akun EvoCare, abaikan email ini.')
            ->salutation('Salam hangat, Tim EvoCare');
    }
}
