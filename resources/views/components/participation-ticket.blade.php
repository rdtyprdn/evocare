@props([
    'registration',
    'showAnswers' => false,
])

@php
    $event = $registration->event;
    $roleLabel = \App\Models\Registration::TYPES[$registration->type] ?? ucfirst($registration->type);
    $ticketCode = $registration->ticketCode();
    $isConfirmed = $registration->status === 'confirmed';
@endphp

<div {{ $attributes->class('relative rounded-xl border border-orange-200 bg-orange-50 overflow-hidden print:overflow-visible') }}>
    <div class="p-4 space-y-2.5">
        <div class="flex items-center justify-between gap-2 flex-wrap">
            <span class="inline-flex items-center rounded-full bg-orange-100 px-2.5 py-0.5 text-xs font-semibold text-orange-800">
                {{ $event->category->name ?? 'Kegiatan Sosial' }}
            </span>
            @if ($isConfirmed)
                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-800">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                    Terkonfirmasi
                </span>
            @else
                <span class="inline-flex items-center gap-1 rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-700">
                    <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                    Dibatalkan
                </span>
            @endif
        </div>

        <h4 class="font-bold text-gray-900 text-base leading-snug">{{ $event->title }}</h4>

        <div class="space-y-1.5 text-xs text-gray-600 pt-1">
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

    {{-- Garis Potong Tiket (Perforated Divider) --}}
    <div class="relative flex items-center justify-between">
        <span class="w-4 h-4 rounded-full bg-white border border-orange-200 -ml-2"></span>
        <div class="flex-1 border-t-2 border-dashed border-orange-200"></div>
        <span class="w-4 h-4 rounded-full bg-white border border-orange-200 -mr-2"></span>
    </div>

    {{-- Bagian Bawah Tiket --}}
    <div class="px-4 py-3 bg-orange-100 flex items-center justify-between text-xs">
        <div>
            <span class="text-gray-500 block">Peran Partisipasi</span>
            <span class="font-bold text-gray-900 text-sm">Sebagai {{ $roleLabel }}</span>
        </div>
        <div class="text-right">
            <span class="text-gray-500 block">Kode Tiket</span>
            <span class="font-mono font-semibold text-gray-800">{{ $ticketCode }}</span>
        </div>
    </div>

    @if ($showAnswers && $registration->relationLoaded('answers') && $registration->answers->isNotEmpty())
        <div class="border-t border-orange-200/70 px-4 py-3 bg-white space-y-1.5">
            <p class="text-xs font-semibold text-gray-700">Jawaban Pendaftaran</p>
            <dl class="grid gap-1 text-xs">
                @foreach ($registration->answers as $a)
                    <div class="flex gap-1.5 min-w-0">
                        <dt class="text-gray-500 shrink-0">{{ $a->label }}:</dt>
                        <dd class="font-medium text-gray-800 break-words">{{ $a->value ?? '-' }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    @endif
</div>
