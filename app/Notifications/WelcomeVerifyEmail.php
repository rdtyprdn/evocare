<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

/** Email selamat datang untuk akun baru, sekaligus berisi tautan verifikasi email. */
class WelcomeVerifyEmail extends VerifyEmail
{
    public function toMail($notifiable): MailMessage
    {
        $url = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Selamat datang di EvoCare, verifikasi email Anda')
            ->greeting('Halo, '.$notifiable->name.'!')
            ->line('Terima kasih sudah membuat akun di EvoCare. Sekarang Anda bisa menjelajahi dan mendaftar kegiatan sosial di sekitar Anda.')
            ->line('Silakan verifikasi alamat email Anda dengan menekan tombol di bawah ini.')
            ->action('Verifikasi Email', $url)
            ->line('Tautan ini berlaku selama '.config('auth.verification.expire', 60).' menit.')
            ->line('Jika Anda tidak merasa membuat akun EvoCare, abaikan email ini.')
            ->salutation('Salam hangat, Tim EvoCare');
    }
}
