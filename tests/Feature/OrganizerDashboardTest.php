<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizerDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function organizer(): User
    {
        return User::factory()->organizer()->create();
    }

    public function test_organizer_dashboard_shows_statistics_and_role_breakdown(): void
    {
        $org = $this->organizer();
        $upcoming = Event::factory()->create([
            'user_id' => $org->id,
            'organization_id' => $org->organization_id,
            'title' => 'Aksi Bersih Sungai',
            'quota' => 10,
            'start_at' => now()->addDays(5),
            'end_at' => now()->addDays(5)->addHours(3),
            'status' => 'published',
        ]);
        $completed = Event::factory()->create([
            'user_id' => $org->id,
            'organization_id' => $org->organization_id,
            'title' => 'Penyuluhan Kesehatan Desa',
            'quota' => 10,
            'start_at' => now()->subDays(5),
            'end_at' => now()->subDays(5)->addHours(2),
            'status' => 'published',
        ]);

        // 2 relawan, 1 donatur, 1 peserta = 4 terkonfirmasi dari 20 kuota (20%) + 1 dibatalkan
        Registration::create(['user_id' => User::factory()->create()->id, 'event_id' => $upcoming->id, 'type' => 'relawan', 'status' => 'confirmed']);
        Registration::create(['user_id' => User::factory()->create()->id, 'event_id' => $upcoming->id, 'type' => 'relawan', 'status' => 'confirmed']);
        Registration::create(['user_id' => User::factory()->create()->id, 'event_id' => $upcoming->id, 'type' => 'donatur', 'status' => 'confirmed']);
        Registration::create(['user_id' => User::factory()->create()->id, 'event_id' => $completed->id, 'type' => 'peserta', 'status' => 'confirmed']);
        Registration::create(['user_id' => User::factory()->create()->id, 'event_id' => $completed->id, 'type' => 'peserta', 'status' => 'cancelled']);

        $response = $this->actingAs($org)->get(route('organizer.events.index'));
        $response->assertOk()
            ->assertSee('Total Kegiatan')
            ->assertSee('Pendaftar Terkonfirmasi')
            ->assertSee('Keterisian Kuota')
            ->assertSee('Peran Partisipan')
            ->assertSee('20%')
            ->assertSee('Aksi Bersih Sungai')
            ->assertSee('Penyuluhan Kesehatan Desa');

        $stats = $response->viewData('stats');
        $this->assertSame(2, $stats['total_events']);
        $this->assertSame(1, $stats['upcoming_events']);
        $this->assertSame(1, $stats['completed_events']);
        $this->assertSame(4, $stats['confirmed_registrations']);
        $this->assertSame(1, $stats['cancelled_registrations']);
        $this->assertSame(20, $stats['occupancy_rate']);
        $this->assertSame(['relawan' => 2, 'donatur' => 1, 'peserta' => 1], $stats['roles']);
    }

    public function test_organizer_can_view_and_filter_schedule_and_detect_conflicts(): void
    {
        $org = $this->organizer();
        $baseStart = now()->addDays(10)->setTime(9, 0);

        $evA = Event::factory()->create([
            'user_id' => $org->id,
            'organization_id' => $org->organization_id,
            'title' => 'Kegiatan Pagi Pertama',
            'start_at' => $baseStart,
            'end_at' => $baseStart->copy()->addHours(3),
            'status' => 'published',
        ]);
        $evB = Event::factory()->create([
            'user_id' => $org->id,
            'organization_id' => $org->organization_id,
            'title' => 'Kegiatan Pagi Bentrok',
            'start_at' => $baseStart->copy()->addHour(),
            'end_at' => $baseStart->copy()->addHours(4),
            'status' => 'published',
        ]);
        $evPast = Event::factory()->create([
            'user_id' => $org->id,
            'organization_id' => $org->organization_id,
            'title' => 'Kegiatan Bulan Lalu',
            'start_at' => now()->subDays(15),
            'end_at' => now()->subDays(15)->addHours(2),
            'status' => 'published',
        ]);

        // Halaman jadwal mendeteksi bentrok antara evA dan evB
        $res = $this->actingAs($org)->get(route('organizer.schedule'));
        $res->assertOk()
            ->assertSee('Bentrok Jadwal Terdeteksi')
            ->assertSee('Kegiatan Pagi Pertama')
            ->assertSee('Kegiatan Pagi Bentrok')
            ->assertSee('Kegiatan Bulan Lalu');

        // Filter hanya kegiatan selesai (completed)
        $this->actingAs($org)->get(route('organizer.schedule', ['period' => 'completed']))
            ->assertOk()
            ->assertSee('Kegiatan Bulan Lalu')
            ->assertDontSee('Kegiatan Pagi Pertama');
    }

    public function test_schedule_shows_clean_indicator_when_no_conflicts_exist(): void
    {
        $org = $this->organizer();
        Event::factory()->create([
            'user_id' => $org->id,
            'organization_id' => $org->organization_id,
            'title' => 'Kegiatan Sabtu',
            'start_at' => now()->addDays(4)->setTime(8, 0),
            'end_at' => now()->addDays(4)->setTime(11, 0),
        ]);
        Event::factory()->create([
            'user_id' => $org->id,
            'organization_id' => $org->organization_id,
            'title' => 'Kegiatan Minggu',
            'start_at' => now()->addDays(5)->setTime(8, 0),
            'end_at' => now()->addDays(5)->setTime(11, 0),
        ]);

        $this->actingAs($org)->get(route('organizer.schedule'))
            ->assertOk()
            ->assertSee('Jadwal Teratur (Tidak Ada Bentrok Jadwal)')
            ->assertDontSee('Bentrok Jadwal Terdeteksi');
    }

    public function test_organizer_can_view_reports_and_save_event_report_summary(): void
    {
        $org = $this->organizer();
        $event = Event::factory()->create([
            'user_id' => $org->id,
            'organization_id' => $org->organization_id,
            'title' => 'Donor Darah Kampus',
            'quota' => 20,
        ]);

        Registration::create([
            'user_id' => User::factory()->create(['name' => 'Budi Santoso'])->id,
            'event_id' => $event->id,
            'type' => 'donatur',
            'status' => 'confirmed',
        ]);

        $this->actingAs($org)->get(route('organizer.reports'))
            ->assertOk()
            ->assertSee('Donor Darah Kampus')
            ->assertSee('Belum Diisi');

        $this->actingAs($org)->put(route('organizer.events.report.update', $event), [
            'report_summary' => 'Kegiatan berjalan lancar, terkumpul bantuan dari para donatur dan relawan.',
        ])->assertRedirect(route('organizer.events.report', $event));

        $this->assertSame(
            'Kegiatan berjalan lancar, terkumpul bantuan dari para donatur dan relawan.',
            $event->fresh()->reportSummary()
        );
        $this->assertNotNull($event->fresh()->reportUpdatedAt());

        $this->actingAs($org)->get(route('organizer.events.report', $event))
            ->assertOk()
            ->assertSee('Kegiatan berjalan lancar, terkumpul bantuan dari para donatur dan relawan.')
            ->assertSee('Budi Santoso');

        $this->actingAs($org)->get(route('organizer.reports'))
            ->assertOk()
            ->assertSee('Sudah Diisi');
    }

    public function test_organizer_can_filter_registrants_by_role_and_export_csv(): void
    {
        $org = $this->organizer();
        $event = Event::factory()->create([
            'user_id' => $org->id,
            'organization_id' => $org->organization_id,
            'title' => 'Penanaman Mangrove',
        ]);

        $relawan = User::factory()->create(['name' => 'Siti Relawan', 'email' => 'siti@example.com']);
        $donatur = User::factory()->create(['name' => 'Agus Donatur', 'email' => 'agus@example.com']);

        Registration::create(['user_id' => $relawan->id, 'event_id' => $event->id, 'type' => 'relawan', 'status' => 'confirmed', 'note' => 'Siap hadir pagi']);
        Registration::create(['user_id' => $donatur->id, 'event_id' => $event->id, 'type' => 'donatur', 'status' => 'confirmed', 'note' => 'Bantuan bibit']);

        // Filter hanya donatur di halaman pendaftar
        $this->actingAs($org)->get(route('organizer.events.registrants', [$event, 'type' => 'donatur']))
            ->assertOk()
            ->assertSee('Agus Donatur')
            ->assertDontSee('Siti Relawan');

        // Ekspor CSV semua pendaftar
        $csvResponse = $this->actingAs($org)->get(route('organizer.events.registrants.export', $event));
        $csvResponse->assertOk();
        $this->assertStringContainsString('text/csv', $csvResponse->headers->get('Content-Type'));
        $csvContent = $csvResponse->streamedContent();
        $this->assertStringContainsString('Penanaman Mangrove', $csvContent);
        $this->assertStringContainsString('Siti Relawan', $csvContent);
        $this->assertStringContainsString('Agus Donatur', $csvContent);
        $this->assertStringContainsString('Siap hadir pagi', $csvContent);
    }

    public function test_other_organizer_or_regular_user_cannot_access_reports_or_export(): void
    {
        $owner = $this->organizer();
        $otherOrganizer = $this->organizer();
        $regularUser = User::factory()->create();

        $event = Event::factory()->create([
            'user_id' => $owner->id,
            'organization_id' => $owner->organization_id,
        ]);

        // Pengguna biasa diarahkan ke pendaftaran organisasi saat membuka jadwal/laporan
        $this->actingAs($regularUser)->get(route('organizer.schedule'))->assertRedirect(route('organizations.create'));
        $this->actingAs($regularUser)->get(route('organizer.reports'))->assertRedirect(route('organizations.create'));

        // Penyelenggara lain ditolak (403) mengakses laporan atau ekspor CSV kegiatan yang bukan miliknya
        $this->actingAs($otherOrganizer)->get(route('organizer.events.report', $event))->assertForbidden();
        $this->actingAs($otherOrganizer)->put(route('organizer.events.report.update', $event), ['report_summary' => 'Bajak'])->assertForbidden();
        $this->actingAs($otherOrganizer)->get(route('organizer.events.registrants.export', $event))->assertForbidden();
    }

    public function test_my_registrations_and_event_detail_use_custom_evocare_cancel_modal(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->create(['title' => 'Aksi Sosial Pantai Sanur']);
        $reg = Registration::create(['user_id' => $user->id, 'event_id' => $event->id, 'type' => 'relawan', 'status' => 'confirmed']);

        $myRegPage = $this->actingAs($user)->get(route('registrations.index'));
        $myRegPage->assertOk()
            ->assertSee('Batalkan Pendaftaran Kegiatan?')
            ->assertSee('Konfirmasi Pembatalan Tiket')
            ->assertSee('Tetap Ikut Kegiatan')
            ->assertSee('Ya, Batalkan Pendaftaran')
            ->assertSee('EVC-'.str_pad((string) $reg->id, 4, '0', STR_PAD_LEFT))
            ->assertDontSee('return confirm(', false);

        $detailPage = $this->actingAs($user)->get(route('events.show', $event));
        $detailPage->assertOk()
            ->assertSee('Batalkan Pendaftaran Kegiatan?')
            ->assertSee('Tetap Ikut Kegiatan')
            ->assertSee('Ya, Batalkan Pendaftaran')
            ->assertDontSee('return confirm(', false);
    }
}
