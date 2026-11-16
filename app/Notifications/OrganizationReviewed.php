<?php

namespace App\Notifications;

use App\Models\Organization;
use App\Notifications\Concerns\QueuedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Hasil peninjauan admin atas pengajuan organisasi (terverifikasi atau ditolak). */
class OrganizationReviewed extends Notification implements ShouldQueue
{
    use QueuedMail;

    public function __construct(public Organization $organization) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $org = $this->organization;
        $mail = (new MailMessage)->greeting('Halo, '.$notifiable->name.'!');

        if ($org->isVerified()) {
            return $mail
                ->subject('Organisasi '.$org->name.' terverifikasi')
                ->line('Pengajuan organisasi "'.$org->name.'" sudah diverifikasi admin EvoCare.')
                ->line('Sekarang Anda bisa membuat dan mengelola kegiatan atas nama organisasi ini.')
                ->action('Buat Kegiatan', route('organizer.events.create'))
                ->salutation('Salam hangat, Tim EvoCare');
        }

        return $mail
            ->subject('Pengajuan organisasi '.$org->name.' ditolak')
            ->line('Pengajuan organisasi "'.$org->name.'" belum bisa kami verifikasi.')
            ->line('Alasan: '.($org->admin_note ?: '-'))
            ->line('Silakan perbaiki data organisasi lalu ajukan ulang.')
            ->action('Perbaiki Pengajuan', route('organizations.create'))
            ->salutation('Salam hangat, Tim EvoCare');
    }
}
