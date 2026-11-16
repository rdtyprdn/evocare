<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Registration;
use App\Models\User;
use App\Support\CsvCell;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CsvExportInjectionTest extends TestCase
{
    use RefreshDatabase;

    /** Unduh CSV pendaftar lalu kembalikan sebagai array baris (BOM dibuang). */
    private function exportRows(User $org, Event $event): array
    {
        $csv = $this->actingAs($org)->get(route('organizer.events.registrants.export', $event))->assertOk()->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);

        $handle = fopen('php://memory', 'r+');
        fwrite($handle, substr($csv, 3));
        rewind($handle);
        $rows = [];
        while (($row = fgetcsv($handle, null, ',', '"', '\\')) !== false) {
            $rows[] = $row;
        }
        fclose($handle);

        return $rows;
    }

    private function registrantRow(array $rows, string $email): array
    {
        foreach ($rows as $row) {
            if (($row[2] ?? null) === $email) {
                return $row;
            }
        }
        $this->fail('Baris pendaftar '.$email.' tidak ditemukan di CSV.');
    }

    public function test_formula_values_in_registrant_csv_are_escaped(): void
    {
        $org = User::factory()->organizer()->create();
        $event = Event::factory()->create(['user_id' => $org->id, 'title' => '=HYPERLINK("http://jahat.test","Klik")']);
        $question = $event->questions()->create([
            'label' => '@Pertanyaan', 'type' => 'text', 'options' => [], 'required' => false, 'roles' => ['relawan'], 'sort_order' => 1,
        ]);

        $cases = [
            'a@contoh.test' => ['=HYPERLINK("http://jahat.test","Klik")', '+1+1', '-2+3'],
            'b@contoh.test' => ['@SUM(A1)', "\tTab=1+1", "\r=1+1"],
            'c@contoh.test' => ['  =1+1', '-', '-5'],
        ];
        foreach ($cases as $email => [$name, $note, $answer]) {
            $user = User::factory()->create(['name' => $name, 'email' => $email]);
            $reg = Registration::create(['user_id' => $user->id, 'event_id' => $event->id, 'type' => 'relawan', 'status' => 'confirmed', 'note' => $note]);
            $reg->answers()->create(['event_question_id' => $question->id, 'label' => '@Pertanyaan', 'type' => 'text', 'value' => $answer, 'sort_order' => 1]);
        }

        $rows = $this->exportRows($org, $event);

        $this->assertSame(['Laporan Kegiatan', '\'=HYPERLINK("http://jahat.test","Klik")'], $rows[0]);
        $header = collect($rows)->first(fn ($row) => ($row[0] ?? null) === 'No');
        $this->assertSame("'@Pertanyaan", $header[7]);

        $a = $this->registrantRow($rows, 'a@contoh.test');
        $this->assertSame('\'=HYPERLINK("http://jahat.test","Klik")', $a[1]);
        $this->assertSame("'+1+1", $a[5]);
        $this->assertSame("'-2+3", $a[7]);

        $b = $this->registrantRow($rows, 'b@contoh.test');
        $this->assertSame("'@SUM(A1)", $b[1]);
        $this->assertSame("'\tTab=1+1", $b[5]);
        $this->assertSame("'\r=1+1", $b[7]);

        $c = $this->registrantRow($rows, 'c@contoh.test');
        $this->assertSame("'  =1+1", $c[1]);
        $this->assertSame('-', $c[5]);
        $this->assertSame('-5', $c[7]);
    }

    public function test_normal_values_in_registrant_csv_are_unchanged(): void
    {
        $org = User::factory()->organizer()->create();
        $event = Event::factory()->create(['user_id' => $org->id, 'title' => 'Bersih Pantai Kuta']);
        $user = User::factory()->create(['name' => 'Budi Santoso', 'email' => 'budi@contoh.test']);
        Registration::create(['user_id' => $user->id, 'event_id' => $event->id, 'type' => 'relawan', 'status' => 'confirmed', 'note' => 'Bawa sarung tangan sendiri']);

        $rows = $this->exportRows($org, $event);

        $this->assertSame(['Laporan Kegiatan', 'Bersih Pantai Kuta'], $rows[0]);
        $this->assertContains('No', $rows[5]);
        $row = $this->registrantRow($rows, 'budi@contoh.test');
        $this->assertSame('1', $row[0]);
        $this->assertSame('Budi Santoso', $row[1]);
        $this->assertSame('Terkonfirmasi', $row[4]);
        $this->assertSame('Bawa sarung tangan sendiri', $row[5]);
    }

    public function test_csv_cell_helper(): void
    {
        foreach (['=1+1', '+1+1', '-2+3', '@SUM(A1)', "\t1", "\r1", ' =1', "\n=1"] as $danger) {
            $this->assertSame("'".$danger, CsvCell::escape($danger));
        }
        foreach (['Budi', 'budi@contoh.test', '65', '-5', '+62', '3,5', '-', '', 'A-1', 12, null] as $safe) {
            $this->assertSame($safe, CsvCell::escape($safe));
        }
    }
}
