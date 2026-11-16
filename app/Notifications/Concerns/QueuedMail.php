<?php

namespace App\Notifications\Concerns;

use Illuminate\Bus\Queueable;

/** Pengaturan bersama untuk email yang dikirim lewat antrian. */
trait QueuedMail
{
    use Queueable;

    /** Jumlah percobaan sebelum job dianggap gagal (masuk failed_jobs). */
    public int $tries = 3;

    /** Jeda antar percobaan ulang, dalam detik. */
    public array $backoff = [10, 60];
}
