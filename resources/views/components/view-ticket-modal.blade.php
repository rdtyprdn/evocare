@props([
    'registration',
    'buttonLabel' => 'Tiket Saya',
    'buttonClass' => 'inline-flex items-center justify-center gap-1.5 rounded-lg border border-orange-200 bg-white px-4 py-2 text-sm font-medium text-orange-600 hover:bg-orange-50 hover:border-orange-300 transition',
])

@php
    $ticketCode = $registration->ticketCode();
    $printUrl = route('registrations.ticket', ['registration' => $registration, 'autoprint' => 1]);
@endphp

<div
    x-data="{ open: false }"
    @keydown.escape.window="open = false"
    class="inline-block w-full sm:w-auto"
>
    <button type="button" @click="open = true" class="{{ $buttonClass }}">
        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 0 1 0 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 0 1 0-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375Z"/>
        </svg>
        <span>{{ $buttonLabel }}</span>
    </button>

    <template x-teleport="body">
        <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6" role="dialog" aria-modal="true" aria-labelledby="ticket-modal-title-{{ $registration->id }}">
            <div x-show="open"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="open = false"
                 class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm"></div>

            <div x-show="open"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="relative w-full max-w-lg rounded-2xl bg-white shadow-2xl border border-gray-100 overflow-hidden text-left">

                <div class="bg-gradient-to-r from-orange-600 via-orange-500 to-orange-600 px-6 py-4 text-white flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <span class="w-8 h-8 rounded-lg bg-white/15 flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 0 1 0 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 0 1 0-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375Z"/>
                            </svg>
                        </span>
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wider text-orange-100">Tiket Partisipasi</p>
                            <h3 id="ticket-modal-title-{{ $registration->id }}" class="text-base font-bold leading-snug">Tiket Saya</h3>
                        </div>
                    </div>
                    <button type="button" @click="open = false" class="rounded-lg p-1.5 text-white/80 hover:text-white hover:bg-white/10 transition" aria-label="Tutup dialog">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="p-6 space-y-5">
                    <p class="text-sm text-gray-600">
                        Simpan atau tunjukkan kode tiket <strong class="font-mono text-gray-800">{{ $ticketCode }}</strong> saat hadir di kegiatan.
                    </p>

                    <x-participation-ticket :registration="$registration" :show-answers="true" />

                    <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2.5 pt-1">
                        <button type="button"
                                @click="open = false"
                                class="w-full sm:w-auto rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                            Tutup
                        </button>
                        <a href="{{ $printUrl }}"
                           target="_blank"
                           rel="noopener"
                           class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-xl bg-orange-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-orange-700 transition">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.244H7.231c-.598 0-1.119-.449-1.121-1.047L6.34 18m11.318 0h1.091c.54 0 .995-.409 1.04-.947l.515-6.188a2.25 2.25 0 0 0-2.239-2.43H7.34a2.25 2.25 0 0 0-2.24 2.43l.515 6.188c.045.538.5.947 1.04.947h1.092M9 11.25h6M9 7.5a3 3 0 0 1 3-3h0a3 3 0 0 1 3 3v.75H9V7.5Z"/>
                            </svg>
                            <span>Cetak / Simpan</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
