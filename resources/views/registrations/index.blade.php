<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">Pendaftaran Saya</h2></x-slot>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @forelse ($registrations as $r)
            <div class="bg-white rounded-xl shadow-sm p-4 mb-3 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <a href="{{ route('events.show', $r->event) }}" class="font-semibold text-lg hover:text-orange-600">{{ $r->event->title }}</a>
                    <p class="text-sm text-gray-500">{{ $r->event->start_at->translatedFormat('d M Y H:i') }} · {{ $r->event->city }} · {{ $r->event->category->name }}</p>
                    <p class="text-sm mt-1">Sebagai <strong>{{ \App\Models\Registration::TYPES[$r->type] }}</strong> ·
                        <span class="{{ $r->status === 'confirmed' ? 'text-green-700' : 'text-red-600' }}">{{ $r->status === 'confirmed' ? 'Terkonfirmasi' : 'Dibatalkan' }}</span></p>
                </div>
                @if ($r->status === 'confirmed')
                    <form method="POST" action="{{ route('registrations.destroy', $r) }}" onsubmit="return confirm('Batalkan pendaftaran?')">
                        @csrf @method('DELETE')
                        <button class="rounded-md border border-red-300 px-4 py-2 text-sm text-red-600 hover:bg-red-50">Batalkan</button>
                    </form>
                @endif
            </div>
        @empty
            <div class="bg-white rounded-xl p-10 text-center text-gray-500">Belum ada pendaftaran. <a href="{{ route('home') }}" class="text-orange-600 underline">Jelajahi kegiatan</a></div>
        @endforelse
    </div>
</x-app-layout>
