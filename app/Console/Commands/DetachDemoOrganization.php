<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Melepas akun nyata dari organisasi demo yang ter-attach otomatis.
 * Kegiatan TIDAK dipindahkan ke organisasi lain: hanya dilepas (organization_id = null)
 * dan akan otomatis terhubung ke organisasi milik user setelah organisasinya diverifikasi.
 */
class DetachDemoOrganization extends Command
{
    protected $signature = 'evocare:detach-org {email* : Email akun yang dilepas} {--dry-run : Hanya tampilkan laporan}';

    protected $description = 'Lepaskan akun dari organisasi demo agar mendaftarkan organisasinya sendiri';

    public function handle(): int
    {
        foreach ($this->argument('email') as $email) {
            $user = User::where('email', $email)->first();
            if (! $user) {
                $this->error("User {$email} tidak ditemukan.");

                continue;
            }
            $org = $user->organization;
            $events = Event::where('user_id', $user->id)->get(['id', 'title', 'organization_id', 'status']);

            $this->info("User #{$user->id} {$user->name} <{$user->email}> role={$user->role}, organisasi=".($org ? "#{$org->id} {$org->name}" : '-'));
            $this->table(['ID', 'Kegiatan', 'Organisasi saat ini', 'Status'], $events->map(fn ($e) => [
                $e->id, $e->title, $e->organization_id ? Organization::find($e->organization_id)?->name : '-', $e->status,
            ]));

            if ($this->option('dry-run')) {
                continue;
            }

            DB::transaction(function () use ($user, $org) {
                if ($org && $org->owner_id === $user->id) {
                    $org->update(['owner_id' => null]);
                }
                Event::where('user_id', $user->id)->when($org, fn ($q) => $q->where('organization_id', $org->id))->update(['organization_id' => null]);
                $user->update(['organization_id' => null, 'role' => $user->isAdmin() ? 'admin' : 'user']);
            });
            $this->info('Selesai: akun dilepas dari organisasi, kegiatan ditandai "belum terhubung organisasi".');
        }

        return self::SUCCESS;
    }
}
