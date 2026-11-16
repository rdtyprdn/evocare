<?php

namespace App\Notifications;

use App\Notifications\Concerns\QueuedMail;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

/** Tautan verifikasi email untuk kirim ulang atau setelah alamat email diganti. */
class VerifyEmailNotification extends VerifyEmail implements ShouldQueue
{
    use QueuedMail;

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Verifikasi alamat email EvoCare Anda')
            ->greeting('Halo, '.$notifiable->name.'!')
            ->line('Tekan tombol di bawah ini untuk memverifikasi alamat email akun EvoCare Anda.')
            ->action('Verifikasi Email', $this->verificationUrl($notifiable))
            ->line('Tautan ini berlaku selama '.config('auth.verification.expire', 60).' menit.')
            ->line('Jika Anda tidak meminta email ini, abaikan saja.')
            ->salutation('Salam hangat, Tim EvoCare');
    }
}
