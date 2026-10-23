<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">Pendaftaran Saya</h2></x-slot>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @forelse ($registrations as $r)
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-3 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:border-orange-200/70 transition">
                <div class="space-y-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <a href="{{ route('events.show', $r->event) }}" class="font-semibold text-lg text-gray-900 hover:text-orange-600">{{ $r->event->title }}</a>
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $r->status === 'confirmed' ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-600' }}">
                            {{ $r->status === 'confirmed' ? 'Terkonfirmasi' : 'Dibatalkan' }}
                        </span>
                    </div>
                    <p class="text-sm text-gray-500">{{ $r->event->start_at->translatedFormat('d M Y H:i') }} · {{ $r->event->city }} · {{ $r->event->category->name }}</p>
                    <p class="text-sm text-gray-700">Sebagai <strong>{{ \App\Models\Registration::TYPES[$r->type] }}</strong></p>
                </div>
                @if ($r->status === 'confirmed')
                    <div class="shrink-0">
                        <x-cancel-registration-modal :registration="$r" button-label="Batalkan" />
                    </div>
                @endif
            </div>
        @empty
            <div class="bg-white rounded-xl p-10 text-center text-gray-500">Belum ada pendaftaran. <a href="{{ route('home') }}" class="text-orange-600 underline">Jelajahi kegiatan</a></div>
        @endforelse
    </div>
</x-app-layout>
