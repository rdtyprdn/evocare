@props([
    'registration',
    'buttonLabel' => 'Batalkan',
    'buttonClass' => 'inline-flex items-center justify-center gap-1.5 rounded-lg border border-red-200 bg-white px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50 hover:border-red-300 transition',
])

@php
    $event = $registration->event;
    $roleLabel = \App\Models\Registration::TYPES[$registration->type] ?? ucfirst($registration->type);
    $ticketCode = 'EVC-'.str_pad((string) $registration->id, 4, '0', STR_PAD_LEFT);
@endphp

<div x-data="{ open: false, submitting: false }" @keydown.escape.window="if (!submitting) open = false" class="inline-block w-full sm:w-auto">
    <button type="button" @click="open = true" class="{{ $buttonClass }}">
        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
        </svg>
        <span>{{ $buttonLabel }}</span>
    </button>

    <template x-teleport="body">
        <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6" role="dialog" aria-modal="true" aria-labelledby="cancel-modal-title-{{ $registration->id }}">
            <!-- Backdrop -->
            <div x-show="open"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="if (!submitting) open = false"
                 class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm"></div>

            <!-- Panel Modal -->
            <div x-show="open"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="relative w-full max-w-lg rounded-2xl bg-white shadow-2xl border border-gray-100 overflow-hidden text-left">

                <!-- Pita Atas Khas EvoCare -->
                <div class="bg-gradient-to-r from-orange-600 via-orange-500 to-red-600 px-6 py-4 text-white flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <span class="w-8 h-8 rounded-lg bg-white/15 flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 0 1 0 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 0 1 0-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375Z"/>
                            </svg>
                        </span>
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wider text-orange-100">Konfirmasi Pembatalan Tiket</p>
                            <h3 id="cancel-modal-title-{{ $registration->id }}" class="text-base font-bold leading-snug">Batalkan Pendaftaran Kegiatan?</h3>
                        </div>
                    </div>
                    <button type="button" @click="if (!submitting) open = false" class="rounded-lg p-1.5 text-white/80 hover:text-white hover:bg-white/10 transition" aria-label="Tutup dialog">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="p-6 space-y-5">
                    <p class="text-sm text-gray-600">
                        Tinjau kembali rincian tiket partisipasi Anda di bawah ini sebelum mengonfirmasi pembatalan keikutsertaan.
                    </p>

                    <!-- Visual Kartu Tiket Partisipasi EvoCare -->
                    <div class="relative rounded-xl border border-orange-200 bg-orange-50/40 overflow-hidden">
                        <div class="p-4 space-y-2.5">
                            <div class="flex items-center justify-between gap-2 flex-wrap">
                                <span class="inline-flex items-center rounded-full bg-orange-100 px-2.5 py-0.5 text-xs font-semibold text-orange-800">
                                    {{ $event->category->name ?? 'Kegiatan Sosial' }}
                                </span>
                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-800">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                    Terkonfirmasi
                                </span>
                            </div>

                            <h4 class="font-bold text-gray-900 text-base leading-snug">{{ $event->title }}</h4>

                            <div class="space-y-1.5 text-xs text-gray-600 pt-1">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-orange-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/></svg>
                                    <span>{{ $event->start_at->translatedFormat('l, d F Y · H:i') }} WITA</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-orange-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>
                                    <span>{{ $event->location ? $event->location.', ' : '' }}{{ $event->city }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Garis Potong Tiket (Perforated Divider) -->
                        <div class="relative flex items-center justify-between">
                            <span class="w-4 h-4 rounded-full bg-white border border-orange-200 -ml-2"></span>
                            <div class="flex-1 border-t-2 border-dashed border-orange-200"></div>
                            <span class="w-4 h-4 rounded-full bg-white border border-orange-200 -mr-2"></span>
                        </div>

                        <!-- Bagian Bawah Tiket -->
                        <div class="px-4 py-3 bg-orange-100/40 flex items-center justify-between text-xs">
                            <div>
                                <span class="text-gray-500 block">Peran Partisipasi</span>
                                <span class="font-bold text-gray-900 text-sm">Sebagai {{ $roleLabel }}</span>
                            </div>
                            <div class="text-right">
                                <span class="text-gray-500 block">Kode Tiket</span>
                                <span class="font-mono font-semibold text-gray-800">{{ $ticketCode }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Informasi Dampak Pembatalan -->
                    <div class="rounded-xl bg-gray-50 border border-gray-200/80 p-3.5 space-y-2 text-xs text-gray-600">
                        <div class="flex items-start gap-2.5">
                            <svg class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/></svg>
                            <span><strong>Slot kuota dikembalikan:</strong> Kursi kuota Anda akan dilepas kembali secara otomatis agar masyarakat lain dapat bergabung.</span>
                        </div>
                        <div class="flex items-start gap-2.5">
                            <svg class="w-4 h-4 text-blue-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                            <span><strong>Dapat mendaftar kembali:</strong> Anda tetap dapat mendaftar ulang selama kuota kegiatan masih tersedia dan kegiatan belum berakhir.</span>
                        </div>
                    </div>

                    <!-- Tombol Aksi -->
                    <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2.5 pt-1">
                        <button type="button"
                                @click="open = false"
                                :disabled="submitting"
                                class="w-full sm:w-auto rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition disabled:opacity-50">
                            Tetap Ikut Kegiatan
                        </button>
                        <form method="POST" action="{{ route('registrations.destroy', $registration) }}" @submit="submitting = true" class="w-full sm:w-auto">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    :disabled="submitting"
                                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-xl bg-red-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-red-700 transition disabled:opacity-60">
                                <svg x-show="!submitting" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                                <svg x-show="submitting" x-cloak class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                <span x-text="submitting ? 'Membatalkan...' : 'Ya, Batalkan Pendaftaran'">Ya, Batalkan Pendaftaran</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
