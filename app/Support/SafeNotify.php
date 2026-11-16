<?php

namespace App\Support;

use Illuminate\Notifications\Notification;

/** Mengirim notifikasi tanpa menggagalkan aksi utama bila pengiriman email bermasalah. */
class SafeNotify
{
    public static function send(object $notifiable, Notification $notification): bool
    {
        try {
            $notifiable->notify($notification);

            return true;
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }
}
