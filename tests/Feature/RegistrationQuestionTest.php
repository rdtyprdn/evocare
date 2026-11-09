<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Event;
use App\Models\EventQuestion;
use App\Models\Registration;
use App\Models\RegistrationAnswer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationQuestionTest extends TestCase
{
    use RefreshDatabase;

    private User $org;

    protected function setUp(): void
    {
        parent::setUp();
        $this->org = User::factory()->organizer()->create();
    }

    private function payload(array $extra = []): array
    {
        return array_merge([
            'title' => 'Donor Darah Bersama', 'category_id' => Category::firstOrCreate(['slug' => 'kesehatan'], ['name' => 'Kesehatan'])->id,
            'description' => 'Deskripsi.', 'city' => 'Denpasar', 'location' => 'PMI Denpasar',
            'start_at' => now()->addDays(3)->format('Y-m-d H:i'), 'end_at' => now()->addDays(3)->addHours(2)->format('Y-m-d H:i'),
            'quota' => 10, 'status' => 'published',
        ], $extra);
    }

    private function eventWithQuestions(): Event
    {
        $event = Event::factory()->create(['user_id' => $this->org->id]);
        $event->questions()->createMany([
            ['label' => 'Golongan darah', 'type' => 'select', 'options' => ['A', 'B', 'AB', 'O'], 'required' => true, 'roles' => ['relawan'], 'sort_order' => 1],
            ['label' => 'Berat badan (kg)', 'type' => 'number', 'options' => [], 'required' => false, 'roles' => ['relawan', 'peserta'], 'sort_order' => 2],
            ['label' => 'Jenis donasi', 'type' => 'select', 'options' => ['Uang', 'Barang'], 'required' => true, 'roles' => ['donatur'], 'sort_order' => 3],
            ['label' => 'Bawa alat sendiri', 'type' => 'boolean', 'options' => [], 'required' => false, 'roles' => ['peserta'], 'sort_order' => 4],
        ]);

        return $event;
    }

    public function test_form_shows_question_builder_with_presets(): void
    {
        $this->actingAs($this->org)->get(route('organizer.events.create'))->assertOk()
            ->assertSee('Pertanyaan Pendaftaran')
            ->assertSee('Gunakan pertanyaan rekomendasi')
            ->assertSee('Golongan darah')
            ->assertSee('name="questions_present"', false);
    }

    public function test_organizer_creates_event_with_questions(): void
    {
        $this->actingAs($this->org)->post(route('organizer.events.store'), $this->payload([
            'questions_present' => 1,
            'questions' => [
                ['label' => 'Golongan darah', 'type' => 'select', 'options' => "A\nB\n\nAB\nO\nA", 'required' => '1', 'roles' => ['relawan', 'peserta']],
                ['label' => 'Jenis donasi', 'type' => 'text', 'options' => 'abaikan', 'roles' => ['donatur']],
            ],
        ]))->assertSessionHasNoErrors()->assertRedirect(route('organizer.events.index'));

        $questions = Event::firstOrFail()->questions;
        $this->assertCount(2, $questions);
        $this->assertSame(['A', 'B', 'AB', 'O'], $questions[0]->options);
        $this->assertTrue($questions[0]->required);
        $this->assertSame(['relawan', 'peserta'], $questions[0]->roles);
        $this->assertSame([], $questions[1]->options);
        $this->assertFalse($questions[1]->required);
        $this->assertSame([1, 2], $questions->pluck('sort_order')->all());
    }

    public function test_question_validation_errors(): void
    {
        $this->actingAs($this->org)->post(route('organizer.events.store'), $this->payload([
            'questions_present' => 1,
            'questions' => [
                ['label' => '', 'type' => 'text', 'roles' => ['relawan']],
                ['label' => 'Pilihan', 'type' => 'select', 'options' => 'Satu', 'roles' => ['relawan']],
                ['label' => 'Tanpa peran', 'type' => 'text'],
                ['label' => 'Tipe aneh', 'type' => 'file', 'roles' => ['relawan']],
            ],
        ]))->assertSessionHasErrors(['questions.0.label', 'questions.2.roles', 'questions.3.type']);
        $this->assertSame(0, Event::count());

        $this->actingAs($this->org)->post(route('organizer.events.store'), $this->payload([
            'questions_present' => 1,
            'questions' => [['label' => 'Pilihan', 'type' => 'select', 'options' => 'Satu', 'roles' => ['relawan']]],
        ]))->assertSessionHasErrors('questions.0.options');
        $this->assertSame(0, Event::count());
    }

    public function test_update_reorders_edits_and_removes_questions_keeping_answer_snapshot(): void
    {
        $event = $this->eventWithQuestions();
        [$golongan, $berat, $donasi, $alat] = $event->questions->all();
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('registrations.store', $event), ['type' => 'relawan', 'answers' => [$golongan->id => 'AB', $berat->id => '60']])
            ->assertSessionHasNoErrors();

        $this->actingAs($this->org)->put(route('organizer.events.update', $event), $this->payload([
            'title' => $event->title, 'category_id' => $event->category_id,
            'questions_present' => 1,
            'questions' => [
                ['id' => $donasi->id, 'label' => 'Jenis donasi', 'type' => 'select', 'options' => "Uang\nBarang", 'required' => '1', 'roles' => ['donatur']],
                ['id' => $golongan->id, 'label' => 'Golongan darah kamu', 'type' => 'select', 'options' => "A\nB\nAB\nO", 'required' => '1', 'roles' => ['relawan']],
                ['id' => 999999, 'label' => 'Ukuran kaos', 'type' => 'text', 'roles' => ['relawan']],
            ],
        ]))->assertSessionHasNoErrors();

        $questions = $event->fresh()->questions;
        $this->assertSame(['Jenis donasi', 'Golongan darah kamu', 'Ukuran kaos'], $questions->pluck('label')->all());
        $this->assertSame($donasi->id, $questions[0]->id);
        $this->assertNotSame(999999, $questions[2]->id);
        $this->assertDatabaseMissing('event_questions', ['id' => $berat->id]);
        $this->assertDatabaseMissing('event_questions', ['id' => $alat->id]);

        // Jawaban lama tetap utuh dengan label saat mendaftar.
        $answers = Registration::firstOrFail()->answers;
        $this->assertSame(['Golongan darah', 'Berat badan (kg)'], $answers->pluck('label')->all());
        $this->assertSame(['AB', '60'], $answers->pluck('value')->all());
        $this->assertSame($golongan->id, $answers[0]->event_question_id);
        $this->assertNull($answers[1]->event_question_id);
    }

    public function test_update_without_question_section_keeps_questions(): void
    {
        $event = $this->eventWithQuestions();
        $this->actingAs($this->org)->put(route('organizer.events.update', $event), $this->payload([
            'title' => $event->title, 'category_id' => $event->category_id,
        ]))->assertSessionHasNoErrors();
        $this->assertSame(4, $event->questions()->count());
    }

    public function test_other_event_question_id_is_not_hijacked(): void
    {
        $other = $this->eventWithQuestions();
        $foreign = $other->questions->first();
        $event = Event::factory()->create(['user_id' => $this->org->id]);

        $this->actingAs($this->org)->put(route('organizer.events.update', $event), $this->payload([
            'title' => $event->title, 'category_id' => $event->category_id,
            'questions_present' => 1,
            'questions' => [['id' => $foreign->id, 'label' => 'Diubah', 'type' => 'text', 'roles' => ['relawan']]],
        ]))->assertSessionHasNoErrors();

        $this->assertSame('Golongan darah', $foreign->fresh()->label);
        $this->assertSame(['Diubah'], $event->questions()->pluck('label')->all());
    }

    public function test_event_page_links_to_form_without_role_or_note_fields(): void
    {
        $event = $this->eventWithQuestions();
        $this->actingAs(User::factory()->create())->get(route('events.show', $event))->assertOk()
            ->assertDontSee('Data pendaftaran sebagai')
            ->assertDontSee('name="answers['.$event->questions[0]->id.']"', false)
            ->assertDontSee('Catatan (opsional)')
            ->assertDontSee('name="type"', false)
            ->assertDontSee('name="note"', false)
            ->assertDontSee('registrationForm(', false)
            ->assertSee('Daftar Sekarang')
            ->assertSee(route('registrations.create', $event), false);
    }

    public function test_event_page_without_questions_still_links_to_form(): void
    {
        $event = Event::factory()->create();
        $this->actingAs(User::factory()->create())->get(route('events.show', $event))->assertOk()
            ->assertDontSee('Data pendaftaran sebagai')
            ->assertDontSee('Kirim Pendaftaran')
            ->assertSee('Daftar Sekarang')
            ->assertSee(route('registrations.create', $event), false);
    }

    public function test_registration_form_page_shows_role_questions(): void
    {
        $event = $this->eventWithQuestions();
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('registrations.create', ['event' => $event, 'role' => 'relawan']))
            ->assertOk()
            ->assertSee('Form Pendaftaran')
            ->assertSee('Data pendaftaran sebagai')
            ->assertSee('Daftar sebagai')
            ->assertSee('Golongan darah')
            ->assertSee('Berat badan (kg)')
            ->assertSee('name="answers['.$event->questions[0]->id.']"', false)
            ->assertSee('Kirim Pendaftaran');

        $this->actingAs($user)->get(route('registrations.create', ['event' => $event, 'role' => 'donatur']))
            ->assertOk()
            ->assertSee('Jenis donasi');
    }

    public function test_registration_form_page_guards_and_allows_roles_without_questions(): void
    {
        $event = $this->eventWithQuestions();
        $user = User::factory()->create();

        $this->get(route('registrations.create', ['event' => $event, 'role' => 'relawan']))
            ->assertRedirect(route('login'));

        // Event tanpa pertanyaan: form tetap bisa dibuka dan dikirim.
        $plain = Event::factory()->create(['user_id' => $this->org->id, 'title' => 'Aksi Tanpa Formulir']);
        $this->actingAs($user)->get(route('registrations.create', ['event' => $plain, 'role' => 'relawan']))
            ->assertOk()
            ->assertSee('Form Pendaftaran')
            ->assertSee('tidak memerlukan data tambahan');

        $this->actingAs($user)->from(route('registrations.create', ['event' => $plain, 'role' => 'relawan']))
            ->post(route('registrations.store', $plain), ['type' => 'peserta', 'note' => 'Siap hadir'])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('registrations.index'));
        $this->assertDatabaseHas('registrations', [
            'user_id' => $user->id,
            'event_id' => $plain->id,
            'type' => 'peserta',
            'status' => 'confirmed',
            'note' => 'Siap hadir',
        ]);

        // Sudah terdaftar di event ber-pertanyaan: form ditolak.
        $other = User::factory()->create();
        $this->actingAs($other)->post(route('registrations.store', $event), [
            'type' => 'relawan',
            'answers' => [$event->questions[0]->id => 'O'],
        ])->assertSessionHasNoErrors();
        $this->actingAs($other)->get(route('registrations.create', ['event' => $event, 'role' => 'relawan']))
            ->assertRedirect(route('events.show', $event));
    }

    public function test_registered_detail_hides_answers_and_points_to_my_registrations(): void
    {
        $event = $this->eventWithQuestions();
        $golongan = $event->questions[0];
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('registrations.store', $event), [
            'type' => 'relawan',
            'answers' => [$golongan->id => 'AB'],
        ])->assertSessionHasNoErrors();

        $this->actingAs($user)->get(route('events.show', $event))->assertOk()
            ->assertSee('Anda terdaftar sebagai')
            ->assertDontSee('Golongan darah:')
            ->assertSee('Pendaftaran Saya');

        $code = \App\Models\Registration::firstOrFail()->ticketCode();
        $this->actingAs($user)->get(route('registrations.index'))->assertOk()
            ->assertSee('Tiket Saya')
            ->assertSee($code)
            ->assertSee('Golongan darah');
    }

    public function test_validation_errors_return_to_registration_form_page(): void
    {
        $event = $this->eventWithQuestions();
        $golongan = $event->questions[0];
        $user = User::factory()->create();
        $formUrl = route('registrations.create', ['event' => $event, 'role' => 'relawan']);

        $this->actingAs($user)
            ->from($formUrl)
            ->post(route('registrations.store', $event), ['type' => 'relawan'])
            ->assertRedirect($formUrl)
            ->assertSessionHasErrors('answers.'.$golongan->id);
    }

    public function test_required_answer_validated_only_for_chosen_role(): void
    {
        $event = $this->eventWithQuestions();
        [$golongan, $berat, $donasi] = $event->questions->all();
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('registrations.store', $event), ['type' => 'relawan'])
            ->assertSessionHasErrors('answers.'.$golongan->id)
            ->assertSessionDoesntHaveErrors('answers.'.$donasi->id);
        $this->assertSame(0, Registration::count());

        $this->actingAs($user)->post(route('registrations.store', $event), ['type' => 'relawan', 'answers' => [$golongan->id => 'Z']])
            ->assertSessionHasErrors('answers.'.$golongan->id);
        $this->actingAs($user)->post(route('registrations.store', $event), ['type' => 'relawan', 'answers' => [$golongan->id => 'O', $berat->id => 'enam puluh']])
            ->assertSessionHasErrors('answers.'.$berat->id);
        $this->assertSame(0, Registration::count());

        // Jawaban untuk peran lain diabaikan dan tidak disimpan.
        $this->actingAs($user)->post(route('registrations.store', $event), ['type' => 'relawan', 'answers' => [$golongan->id => 'O', $berat->id => '58.5', $donasi->id => 'Uang']])
            ->assertSessionHasNoErrors()->assertRedirect(route('registrations.index'));

        $reg = Registration::firstOrFail();
        $this->assertSame(['Golongan darah' => 'O', 'Berat badan (kg)' => '58.5'], $reg->answers->pluck('value', 'label')->all());
    }

    public function test_donatur_and_boolean_answers(): void
    {
        $event = $this->eventWithQuestions();
        [, $berat, $donasi, $alat] = $event->questions->all();

        $this->actingAs(User::factory()->create())->post(route('registrations.store', $event), ['type' => 'donatur'])
            ->assertSessionHasErrors('answers.'.$donasi->id);
        $this->actingAs(User::factory()->create())->post(route('registrations.store', $event), ['type' => 'donatur', 'answers' => [$donasi->id => 'Barang']])
            ->assertSessionHasNoErrors();

        $peserta = User::factory()->create();
        $this->actingAs($peserta)->post(route('registrations.store', $event), ['type' => 'peserta', 'answers' => [$alat->id => 'maybe']])
            ->assertSessionHasErrors('answers.'.$alat->id);
        $this->actingAs($peserta)->post(route('registrations.store', $event), ['type' => 'peserta', 'answers' => [$alat->id => 'ya']])
            ->assertSessionHasNoErrors();

        $answers = Registration::where('user_id', $peserta->id)->firstOrFail()->answers;
        $this->assertSame(['Berat badan (kg)' => null, 'Bawa alat sendiri' => 'Ya'], $answers->pluck('value', 'label')->all());
        $this->assertSame(3, RegistrationAnswer::count());
    }

    public function test_reregistering_after_cancel_replaces_answers(): void
    {
        $event = $this->eventWithQuestions();
        [$golongan, , $donasi] = $event->questions->all();
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('registrations.store', $event), ['type' => 'relawan', 'answers' => [$golongan->id => 'A']]);
        $reg = Registration::firstOrFail();
        $this->actingAs($user)->delete(route('registrations.destroy', $reg));
        $this->actingAs($user)->post(route('registrations.store', $event), ['type' => 'donatur', 'answers' => [$donasi->id => 'Uang']])
            ->assertSessionHasNoErrors();

        $this->assertSame(['Jenis donasi' => 'Uang'], $reg->fresh()->answers->pluck('value', 'label')->all());
    }

    public function test_registrants_page_and_csv_show_answers(): void
    {
        $event = $this->eventWithQuestions();
        [$golongan, $berat] = $event->questions->all();
        $user = User::factory()->create(['name' => 'Budi Relawan']);
        $this->actingAs($user)->post(route('registrations.store', $event), ['type' => 'relawan', 'answers' => [$golongan->id => 'AB', $berat->id => '65']]);

        $this->actingAs($this->org)->get(route('organizer.events.registrants', $event))->assertOk()
            ->assertSee('Data Pendaftaran')->assertSee('Golongan darah:')->assertSee('AB');

        $csv = $this->actingAs($this->org)->get(route('organizer.events.registrants.export', $event))->assertOk()->streamedContent();
        $this->assertStringContainsString('"Tanggal Daftar","Golongan darah","Berat badan (kg)","Jenis donasi","Bawa alat sendiri"', $csv);
        $this->assertMatchesRegularExpression('/Budi Relawan.*,AB,65,,/', $csv);
    }

    public function test_presets_cover_main_categories(): void
    {
        $presets = EventQuestion::presets();
        foreach (['kesehatan', 'darurat-bencana', 'pendidikan', 'lingkungan', 'pemberdayaan-ekonomi', 'donatur'] as $slug) {
            $this->assertNotEmpty($presets[$slug]);
            foreach ($presets[$slug] as $p) {
                $this->assertArrayHasKey($p['type'], EventQuestion::TYPES);
                $this->assertNotEmpty($p['roles']);
            }
        }
        $this->assertSame('Golongan darah', $presets['kesehatan'][0]['label']);
    }
}
