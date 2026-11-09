@php
    use App\Models\EventQuestion;
    use App\Models\Registration;

    $questionErrors = collect($errors->getMessages())
        ->filter(fn ($m, $k) => $k === 'questions' || str_starts_with($k, 'questions.'))
        ->flatten()->unique()->values();
    $qIcons = [
        'list' => 'M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z',
        'bulb' => 'M12 18v-5.25m0 0a6.01 6.01 0 0 0 1.5-.189m-1.5.189a6.01 6.01 0 0 1-1.5-.189m3.75 7.478a12.06 12.06 0 0 1-4.5 0m3.75 2.383a14.406 14.406 0 0 1-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 1 0-7.517 0c.85.493 1.509 1.333 1.509 2.316V18',
        'plus' => 'M12 4.5v15m7.5-7.5h-15',
        'up' => 'm4.5 15.75 7.5-7.5 7.5 7.5',
        'down' => 'm19.5 8.25-7.5 7.5-7.5-7.5',
        'trash' => 'm14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0',
        'info' => 'm11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z',
    ];
@endphp
<section id="pertanyaan" class="rounded-xl border border-gray-200 p-5 space-y-4 scroll-mt-4"
         x-data="questionBuilder(@js($questionState), @js(EventQuestion::presets()), @js($categories->pluck('slug', 'id')))">
    <input type="hidden" name="questions_present" value="1">
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
        <div>
            <h3 class="text-base font-semibold text-gray-900">Pertanyaan Pendaftaran <span class="text-sm font-normal text-gray-500">(opsional)</span></h3>
            <p class="text-sm text-gray-500">Data tambahan yang perlu diisi pendaftar, bisa berbeda untuk Relawan, Donatur, dan Peserta. Contoh: golongan darah untuk kegiatan donor darah.</p>
        </div>
        <span class="text-sm text-gray-500 shrink-0"><span x-text="questions.length">{{ count($questionState) }}</span> / {{ EventQuestion::MAX_PER_EVENT }} pertanyaan</span>
    </div>

    <div class="flex flex-wrap gap-2">
        <button type="button" @click="applyPresets()" class="inline-flex items-center gap-1.5 rounded-lg border border-orange-200 bg-orange-50 px-3 py-2 text-sm font-medium text-orange-700 hover:bg-orange-100 transition">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $qIcons['bulb'] }}"/></svg>
            Gunakan pertanyaan rekomendasi
        </button>
        <button type="button" @click="add()" class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $qIcons['plus'] }}"/></svg>
            Tambah pertanyaan
        </button>
    </div>

    <p x-show="notice" x-cloak x-text="notice" class="text-sm text-orange-800 bg-orange-50 border border-orange-100 rounded-md px-3 py-2" role="status"></p>
    <p x-show="error" x-cloak x-text="error" class="text-sm text-red-600" role="alert"></p>
    @if ($questionErrors->isNotEmpty())
        <ul class="text-sm text-red-600 space-y-1" role="alert">
            @foreach ($questionErrors as $msg)<li>{{ $msg }}</li>@endforeach
        </ul>
    @endif

    <div x-show="!questions.length" @if (count($questionState)) x-cloak @endif class="flex items-center gap-3 rounded-lg border border-dashed border-gray-300 px-4 py-5 text-sm text-gray-500">
        <svg class="w-6 h-6 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $qIcons['list'] }}"/></svg>
        <span>Belum ada pertanyaan. Pendaftar cukup memilih peran dan mengisi catatan.</span>
    </div>

    <ol class="space-y-3">
        <template x-for="(q, i) in questions" :key="q.key">
            <li class="rounded-lg border border-gray-200 bg-white p-4 space-y-3">
                <input type="hidden" :name="`questions[${i}][id]`" :value="q.id ?? ''">
                <div class="flex items-start gap-3">
                    <span class="mt-2 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-orange-100 text-xs font-semibold text-orange-700" x-text="i + 1"></span>
                    <div class="flex-1 min-w-0">
                        <label class="sr-only" :for="`q-label-${q.key}`">Pertanyaan</label>
                        <input type="text" data-question-label :id="`q-label-${q.key}`" :name="`questions[${i}][label]`" x-model="q.label" maxlength="150" required
                               placeholder="Tulis pertanyaan, misalnya Golongan darah"
                               class="w-full rounded-md border-gray-300 text-sm focus:border-orange-500 focus:ring-orange-500">
                    </div>
                    <div class="flex shrink-0 items-center gap-1 pt-1">
                        <button type="button" @click="move(i, -1)" :disabled="i === 0" class="rounded-md p-1.5 text-gray-500 hover:bg-gray-100 disabled:opacity-30" title="Naikkan" aria-label="Naikkan pertanyaan">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $qIcons['up'] }}"/></svg>
                        </button>
                        <button type="button" @click="move(i, 1)" :disabled="i === questions.length - 1" class="rounded-md p-1.5 text-gray-500 hover:bg-gray-100 disabled:opacity-30" title="Turunkan" aria-label="Turunkan pertanyaan">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $qIcons['down'] }}"/></svg>
                        </button>
                        <button type="button" @click="remove(i)" class="rounded-md p-1.5 text-red-500 hover:bg-red-50" title="Hapus" aria-label="Hapus pertanyaan">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $qIcons['trash'] }}"/></svg>
                        </button>
                    </div>
                </div>

                <div class="grid gap-3 sm:grid-cols-2 sm:pl-9">
                    <div>
                        <label class="block text-xs font-medium text-gray-600" :for="`q-type-${q.key}`">Jenis jawaban</label>
                        <select :id="`q-type-${q.key}`" :name="`questions[${i}][type]`" x-model="q.type" class="mt-1 w-full rounded-md border-gray-300 text-sm focus:border-orange-500 focus:ring-orange-500">
                            @foreach (EventQuestion::TYPES as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <span class="block text-xs font-medium text-gray-600">Ditanyakan kepada</span>
                        <div class="mt-1 flex flex-wrap gap-1.5">
                            @foreach (Registration::TYPES as $val => $label)
                                <label class="cursor-pointer">
                                    <input type="checkbox" class="peer sr-only" value="{{ $val }}" :name="`questions[${i}][roles][]`" x-model="q.roles">
                                    <span class="inline-flex items-center rounded-full border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-600 transition peer-checked:border-orange-600 peer-checked:bg-orange-600 peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-orange-300">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                        <p x-show="!q.roles.length" x-cloak class="mt-1 text-xs text-red-600">Pilih minimal satu peran.</p>
                    </div>
                    <div x-show="q.type === 'select'" x-cloak class="sm:col-span-2">
                        <label class="block text-xs font-medium text-gray-600" :for="`q-opt-${q.key}`">Daftar pilihan <span class="font-normal text-gray-400">(satu pilihan per baris)</span></label>
                        <textarea :id="`q-opt-${q.key}`" :name="`questions[${i}][options]`" x-model="q.options" :disabled="q.type !== 'select'" :rows="Math.min(8, Math.max(3, q.options.split('\n').length))"
                                  placeholder="A&#10;B&#10;AB&#10;O" class="mt-1 w-full rounded-md border-gray-300 text-sm focus:border-orange-500 focus:ring-orange-500"></textarea>
                    </div>
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700 sm:col-span-2">
                        <input type="checkbox" value="1" :name="`questions[${i}][required]`" x-model="q.required" class="rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                        Wajib diisi
                    </label>
                </div>

                <p x-show="q.answers > 0" x-cloak class="flex items-start gap-1.5 text-xs text-gray-500 sm:pl-9">
                    <svg class="w-4 h-4 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $qIcons['info'] }}"/></svg>
                    <span>Sudah dijawab <strong x-text="q.answers"></strong> pendaftar. Jawaban lama tetap tersimpan sesuai pertanyaan saat mereka mendaftar.</span>
                </p>
            </li>
        </template>
    </ol>
</section>
