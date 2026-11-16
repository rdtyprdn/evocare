<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\EventPhoto;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Mencari file unggahan yang tidak lagi dipakai baris mana pun di database
 * (sisa kegiatan atau akun yang sudah dihapus). Tanpa --force hanya menampilkan laporan.
 */
class PruneOrphanedFiles extends Command
{
    protected $signature = 'evocare:prune-files {--force : Benar-benar hapus file tidak terpakai yang ditemukan}';

    protected $description = 'Cari dan hapus file unggahan yang tidak lagi terhubung ke data mana pun';

    /** Folder di disk public yang berisi file unggahan pengguna. */
    private const DIRECTORIES = ['events', 'event-photos', 'event-videos', 'avatars', 'organizations'];

    public function handle(): int
    {
        $public = Storage::disk('public');
        $local = Storage::disk('local');

        $used = collect()
            ->merge(Event::whereNotNull('image_path')->pluck('image_path'))
            ->merge(EventPhoto::whereNotNull('path')->pluck('path'))
            ->merge(User::whereNotNull('avatar_path')->pluck('avatar_path'))
            ->merge(Organization::whereNotNull('logo_path')->pluck('logo_path'))
            ->flip();

        $orphans = collect(self::DIRECTORIES)
            ->flatMap(fn ($dir) => $public->allFiles($dir))
            ->reject(fn ($path) => $used->has($path) || str_starts_with(basename($path), '.'))
            ->values();

        $eventIds = Event::pluck('id')->flip();
        $reports = collect($local->files('reports'))
            ->filter(fn ($path) => preg_match('#^reports/event-(\d+)\.json$#', $path, $m) && ! $eventIds->has((int) $m[1]))
            ->values();

        if ($orphans->isEmpty() && $reports->isEmpty()) {
            $this->info('Tidak ada file tidak terpakai.');

            return self::SUCCESS;
        }

        $bytes = $orphans->sum(fn ($path) => $public->size($path));
        $this->table(['Disk', 'File'], $orphans->map(fn ($p) => ['public', $p])->merge($reports->map(fn ($p) => ['local', $p])));
        $this->line(sprintf('Total: %d file (%.2f MB).', $orphans->count() + $reports->count(), $bytes / 1048576));

        if (! $this->option('force')) {
            $this->comment('Mode simulasi, belum ada yang dihapus. Jalankan ulang dengan --force untuk menghapus.');

            return self::SUCCESS;
        }

        $public->delete($orphans->all());
        $local->delete($reports->all());

        foreach (['event-photos', 'event-videos'] as $dir) {
            foreach ($public->directories($dir) as $sub) {
                if ($public->allFiles($sub) === []) {
                    $public->deleteDirectory($sub);
                }
            }
        }

        $this->info('File tidak terpakai sudah dihapus.');

        return self::SUCCESS;
    }
}
