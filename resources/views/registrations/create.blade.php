@php
    use App\Models\Registration;

    $fieldClass = 'mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-orange-500 focus:ring-orange-500';
    $questionRoles = $questions->mapWithKeys(fn ($q) => [$q->id => $q->roles ?? []]);
    $initialRole = old('type', $role);
    $hasInitial = $questions->contains(fn ($q) => $q->appliesTo($initialRole));
    $answerErrors = collect($errors->getMessages())->filter(fn ($m, $k) => str_starts_with($k, 'answers.'));
    $sisa = $event->remainingQuota();
@endphp
<x-app-layout>
    <x-slot name="title">Daftar · {{ $event->title }}</x-slot>
    <x-slot name="header">
        <div class="space-y-1">
            <p class="text-sm text-gray-500">
                <a href="{{ route('events.show', $event) }}" class="hover:text-orange-600">{{ $event->title }}</a>
                <span class="mx-1 text-gray-300">/</span>
                Lengkapi Data Pendaftaran
            </p>
            <h2 class="font-semibold text-xl text-gray-900">Form Pendaftaran</h2>
        </div>
    </x-slot>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6"
         x-data="registrationForm(@js($initialRole), @js((object) $questionRoles->all()))">

        {{-- Ringkasan kegiatan --}}
        <div class="rounded-xl border border-orange-200 bg-orange-50/50 p-5 space-y-3">
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center rounded-full bg-orange-100 px-2.5 py-0.5 text-xs font-semibold text-orange-800">
                    {{ $event->category->name ?? 'Kegiatan' }}
                </span>
                <span class="text-xs text-gray-500">Sisa {{ $sisa }} tempat</span>
            </div>
            <h3 class="text-lg font-bold text-gray-900">{{ $event->title }}</h3>
            <div class="space-y-1.5 text-sm text-gray-600">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-orange-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/></svg>
                    <span>{{ $event->start_at->translatedFormat('l, d F Y · H:i') }} WITA</span>
                </div>
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-orange-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>
                    <span>{{ $event->location ? $event->location.', ' : '' }}{{ $event->city }}</span>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('registrations.store', $event) }}" class="rounded-xl border border-gray-200 bg-white p-5 sm:p-6 space-y-5 shadow-sm">
            @csrf

            <div class="flex items-start gap-3">
                <span class="w-9 h-9 rounded-lg bg-orange-100 text-orange-700 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z"/></svg>
                </span>
                <div>
                    <h3 class="font-bold text-gray-900">Data pendaftaran sebagai <span x-text="@js(Registration::TYPES)[role]">{{ Registration::TYPES[$initialRole] ?? '' }}</span></h3>
                    <p class="text-sm text-gray-500">Pilih peranmu, isi data yang diminta (jika ada), lalu kirim pendaftaran. Kolom bertanda <span class="text-red-600">*</span> wajib diisi.</p>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="reg-type" class="text-sm font-medium text-gray-700">Daftar sebagai</label>
                    <select id="reg-type" name="type" x-model="role" class="{{ $fieldClass }}">
                        @foreach (Registration::TYPES as $val => $label)
                            <option value="{{ $val }}" @selected($initialRole === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('type')" class="mt-1" />
                </div>
                <div>
                    <label for="reg-note" class="text-sm font-medium text-gray-700">Catatan <span class="font-normal text-gray-400">(opsional)</span></label>
                    <textarea id="reg-note" name="note" rows="1" placeholder="Catatan untuk penyelenggara" class="{{ $fieldClass }}">{{ old('note', $note) }}</textarea>
                    <x-input-error :messages="$errors->get('note')" class="mt-1" />
                </div>
            </div>

            @if ($answerErrors->isNotEmpty())
                <p class="text-sm text-red-600 rounded-lg border border-red-200 bg-red-50 px-3 py-2" role="alert">
                    Masih ada data pendaftaran yang belum sesuai. Periksa kolom yang ditandai.
                </p>
            @endif

            <div x-show="visibleCount() > 0" @unless ($hasInitial) x-cloak @endunless class="space-y-4 border-t border-gray-100 pt-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach ($questions as $q)
                        @php
                            $fid = 'answer-'.$q->id;
                            $fname = 'answers['.$q->id.']';
                            $val = old('answers.'.$q->id);
                        @endphp
                        <div x-show="shows({{ $q->id }})" @unless ($q->appliesTo($initialRole)) x-cloak @endunless class="{{ $q->type === 'textarea' ? 'sm:col-span-2' : '' }}">
                            @if ($q->type === 'boolean')
                                <fieldset>
                                    <legend class="text-sm font-medium text-gray-700">{{ $q->label }}@if ($q->required)<span class="text-red-600"> *</span>@endif</legend>
                                    <div class="mt-2 flex gap-2">
                                        @foreach (['ya' => 'Ya', 'tidak' => 'Tidak'] as $bv => $bl)
                                            <label class="cursor-pointer">
                                                <input type="radio" class="peer sr-only" name="{{ $fname }}" value="{{ $bv }}" @checked($val === $bv) @if ($q->required && $loop->first) required @endif :disabled="!shows({{ $q->id }})">
                                                <span class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition peer-checked:border-orange-600 peer-checked:bg-orange-50 peer-checked:text-orange-700 peer-focus-visible:ring-2 peer-focus-visible:ring-orange-300">{{ $bl }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </fieldset>
                            @else
                                <label for="{{ $fid }}" class="text-sm font-medium text-gray-700">{{ $q->label }}@if ($q->required)<span class="text-red-600"> *</span>@endif</label>
                                @if ($q->type === 'select')
                                    <select id="{{ $fid }}" name="{{ $fname }}" class="{{ $fieldClass }}" @required($q->required) :disabled="!shows({{ $q->id }})">
                                        <option value="">Pilih salah satu</option>
                                        @foreach ($q->options ?? [] as $opt)
                                            <option value="{{ $opt }}" @selected($val === $opt)>{{ $opt }}</option>
                                        @endforeach
                                    </select>
                                @elseif ($q->type === 'textarea')
                                    <textarea id="{{ $fid }}" name="{{ $fname }}" rows="3" maxlength="2000" class="{{ $fieldClass }}" @required($q->required) :disabled="!shows({{ $q->id }})">{{ $val }}</textarea>
                                @elseif ($q->type === 'number')
                                    <input type="number" step="any" min="0" id="{{ $fid }}" name="{{ $fname }}" value="{{ $val }}" class="{{ $fieldClass }}" @required($q->required) :disabled="!shows({{ $q->id }})">
                                @elseif ($q->type === 'date')
                                    <input type="date" id="{{ $fid }}" name="{{ $fname }}" value="{{ $val }}" class="{{ $fieldClass }}" @required($q->required) :disabled="!shows({{ $q->id }})">
                                @else
                                    <input type="text" maxlength="255" id="{{ $fid }}" name="{{ $fname }}" value="{{ $val }}" class="{{ $fieldClass }}" @required($q->required) :disabled="!shows({{ $q->id }})">
                                @endif
                            @endif
                            <x-input-error :messages="$errors->get('answers.'.$q->id)" class="mt-1" />
                        </div>
                    @endforeach
                </div>
            </div>

            <div x-show="visibleCount() === 0" x-cloak class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-600">
                Peran ini tidak memerlukan data tambahan. Kamu bisa langsung mengirim pendaftaran.
            </div>

            <div class="flex flex-col-reverse sm:flex-row sm:justify-between gap-3 pt-2 border-t border-gray-100">
                <a href="{{ route('events.show', $event) }}" class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/></svg>
                    Kembali
                </a>
                <button type="submit"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-orange-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-orange-700 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                    Kirim Pendaftaran
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
