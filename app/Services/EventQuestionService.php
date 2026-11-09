<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventQuestion;
use App\Models\Registration;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Pertanyaan pendaftaran per kegiatan: validasi isian form penyelenggara dan sinkronisasi ke database.
 * Jawaban pendaftar menyimpan salinan label, jadi mengubah atau menghapus pertanyaan tidak merusak jawaban lama.
 */
class EventQuestionService
{
    /** null berarti form tidak menyertakan bagian pertanyaan, sehingga pertanyaan lama dibiarkan. */
    public function validated(Request $request): ?array
    {
        if (! $request->boolean('questions_present')) {
            return null;
        }

        $request->validate([
            'questions' => ['nullable', 'array', 'max:'.EventQuestion::MAX_PER_EVENT],
            'questions.*.id' => ['nullable', 'integer'],
            'questions.*.label' => ['required', 'string', 'max:150'],
            'questions.*.type' => ['required', Rule::in(array_keys(EventQuestion::TYPES))],
            'questions.*.options' => ['nullable', 'string', 'max:3000'],
            'questions.*.required' => ['nullable', 'boolean'],
            'questions.*.roles' => ['required', 'array', 'min:1'],
            'questions.*.roles.*' => [Rule::in(array_keys(Registration::TYPES))],
        ], [
            'questions.max' => 'Pertanyaan pendaftaran maksimal '.EventQuestion::MAX_PER_EVENT.' butir.',
            'questions.*.label.required' => 'Pertanyaan nomor :position belum diisi.',
            'questions.*.label.max' => 'Pertanyaan nomor :position maksimal 150 karakter.',
            'questions.*.type.*' => 'Jenis jawaban pertanyaan nomor :position tidak valid.',
            'questions.*.roles.required' => 'Pilih minimal satu peran untuk pertanyaan nomor :position.',
            'questions.*.roles.min' => 'Pilih minimal satu peran untuk pertanyaan nomor :position.',
            'questions.*.roles.*.in' => 'Peran pada pertanyaan nomor :position tidak valid.',
        ]);

        $out = [];
        $errors = [];
        foreach (array_values((array) $request->input('questions', [])) as $i => $q) {
            $options = [];
            if ($q['type'] === 'select') {
                $options = array_values(array_unique(array_filter(
                    array_map('trim', preg_split('/\r\n|\r|\n/', (string) ($q['options'] ?? ''))),
                    fn ($o) => $o !== ''
                )));
                $options = array_map(fn ($o) => mb_substr($o, 0, 100), $options);
                if (count($options) < 2) {
                    $errors["questions.$i.options"] = 'Pertanyaan nomor '.($i + 1).' bertipe pilihan, isi minimal 2 pilihan (satu per baris).';
                } elseif (count($options) > EventQuestion::MAX_OPTIONS) {
                    $errors["questions.$i.options"] = 'Pertanyaan nomor '.($i + 1).' maksimal '.EventQuestion::MAX_OPTIONS.' pilihan.';
                }
            }
            $out[] = [
                'id' => isset($q['id']) && $q['id'] !== '' ? (int) $q['id'] : null,
                'label' => trim($q['label']),
                'type' => $q['type'],
                'options' => $options,
                'required' => filter_var($q['required'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'roles' => array_values(array_intersect(array_keys(Registration::TYPES), (array) $q['roles'])),
                'sort_order' => $i + 1,
            ];
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        return $out;
    }

    public function sync(Event $event, ?array $questions): void
    {
        if ($questions === null) {
            return;
        }

        $existing = $event->questions()->get()->keyBy('id');
        $keep = [];
        foreach ($questions as $q) {
            $attrs = collect($q)->except('id')->all();
            $model = $q['id'] ? $existing->get($q['id']) : null;
            if ($model) {
                $model->update($attrs);
            } else {
                $model = $event->questions()->create($attrs);
            }
            $keep[] = $model->id;
        }

        // Jawaban lama tetap ada karena event_question_id di-null-kan dan label sudah disalin.
        $event->questions()->whereNotIn('id', $keep)->delete();
    }

    /** Data awal untuk pembangun pertanyaan (Alpine) di form kegiatan. */
    public function builderState(Event $event): array
    {
        $old = old('questions');
        if (old('questions_present') && is_array($old)) {
            $counts = $event->exists ? $event->questions()->withCount('answers')->pluck('answers_count', 'id') : collect();

            return array_values(array_map(fn ($q) => [
                'id' => $q['id'] ?? null,
                'label' => (string) ($q['label'] ?? ''),
                'type' => array_key_exists($q['type'] ?? '', EventQuestion::TYPES) ? $q['type'] : 'text',
                'options' => (string) ($q['options'] ?? ''),
                'required' => filter_var($q['required'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'roles' => array_values((array) ($q['roles'] ?? [])),
                'answers' => (int) ($counts[$q['id'] ?? 0] ?? 0),
            ], $old));
        }

        if (! $event->exists) {
            return [];
        }

        return $event->questions()->withCount('answers')->get()->map(fn (EventQuestion $q) => [
            'id' => $q->id,
            'label' => $q->label,
            'type' => $q->type,
            'options' => implode("\n", $q->options ?? []),
            'required' => $q->required,
            'roles' => $q->roles ?? [],
            'answers' => $q->answers_count,
        ])->all();
    }
}
