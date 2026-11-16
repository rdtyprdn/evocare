<?php

namespace App\Notifications;

use App\Notifications\Concerns\QueuedMail;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

/** Tautan atur ulang kata sandi dalam bahasa Indonesia. */
class ResetPasswordNotification extends ResetPassword implements ShouldQueue
{
    use QueuedMail;

    public function toMail($notifiable): MailMessage
    {
        $expire = config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60);

        return (new MailMessage)
            ->subject('Atur ulang kata sandi EvoCare')
            ->greeting('Halo, '.$notifiable->name.'!')
            ->line('Kami menerima permintaan untuk mengatur ulang kata sandi akun EvoCare Anda.')
            ->action('Atur Ulang Kata Sandi', $this->resetUrl($notifiable))
            ->line('Tautan ini berlaku selama '.$expire.' menit.')
            ->line('Jika Anda tidak meminta atur ulang kata sandi, abaikan email ini. Kata sandi Anda tidak berubah.')
            ->salutation('Salam hangat, Tim EvoCare');
    }
}
