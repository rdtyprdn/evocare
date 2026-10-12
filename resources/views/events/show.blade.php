<x-app-layout>
    <x-slot name="title">{{ $event->title }}</x-slot>
    <img src="{{ $event->image_url ?: 'https://picsum.photos/seed/'.$event->id.'/1200/400' }}" alt="{{ $event->title }}" class="w-full h-64 object-cover">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 grid gap-8 lg:grid-cols-3">
        <div class="lg:col-span-2 space-y-4">
            <span class="text-sm font-semibold uppercase text-orange-600">{{ $event->category->name }}</span>
            <h1 class="text-3xl font-bold">{{ $event->title }}</h1>
            @if ($event->status !== 'published')
                <span class="inline-block px-2 py-1 text-xs rounded bg-gray-200">{{ \App\Models\Event::STATUSES[$event->status] }}</span>
            @endif
            <p class="text-gray-600">Diselenggarakan oleh <strong>{{ $event->organization->name }}</strong> ({{ \App\Models\Organization::TYPES[$event->organization->type] ?? $event->organization->type }})</p>
            <div class="prose max-w-none whitespace-pre-line">{{ $event->description }}</div>
        </div>
        <aside class="bg-white rounded-xl shadow-sm p-6 h-fit space-y-3">
            <p><strong>{{ $event->start_at->translatedFormat('l, d F Y') }}</strong><br><span class="text-sm text-gray-600">{{ $event->start_at->format('H:i') }} – {{ $event->end_at->format('d M Y H:i') }}</span></p>
            <p><span class="text-gray-500">Lokasi:</span> {{ $event->location }}, {{ $event->city }}</p>
            @php $sisa = $event->remainingQuota(); @endphp
            <p>Sisa kuota: <strong class="{{ $sisa ? 'text-green-700' : 'text-red-600' }}">{{ $sisa }}</strong> dari {{ $event->quota }}</p>
            <hr>
            @auth
                @if ($myRegistration && $myRegistration->status === 'confirmed')
                    <div class="rounded-md bg-green-50 p-3 text-green-800 text-sm">Anda terdaftar sebagai <strong>{{ \App\Models\Registration::TYPES[$myRegistration->type] }}</strong>.</div>
                    <form method="POST" action="{{ route('registrations.destroy', $myRegistration) }}" onsubmit="return confirm('Batalkan pendaftaran?')">
                        @csrf @method('DELETE')
                        <button class="w-full rounded-md border border-red-300 py-2 text-red-600 hover:bg-red-50">Batalkan Pendaftaran</button>
                    </form>
                @elseif ($event->status === 'published' && $sisa > 0 && $event->end_at->isFuture())
                    <form method="POST" action="{{ route('registrations.store', $event) }}" class="space-y-3">
                        @csrf
                        <label class="block text-sm font-medium">Daftar sebagai</label>
                        <select name="type" class="w-full rounded-md border-gray-300">
                            @foreach (\App\Models\Registration::TYPES as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <textarea name="note" rows="2" placeholder="Catatan (opsional)" class="w-full rounded-md border-gray-300"></textarea>
                        <x-input-error :messages="$errors->get('type')" />
                        <button class="w-full rounded-md bg-orange-600 py-3 text-white font-semibold hover:bg-orange-700">Daftar Sekarang</button>
                    </form>
                @else
                    <div class="rounded-md bg-gray-100 p-3 text-sm text-gray-600">Pendaftaran tidak tersedia (kuota penuh atau kegiatan ditutup).</div>
                @endif
            @else
                <a href="{{ route('login') }}" class="block text-center w-full rounded-md bg-orange-600 py-3 text-white font-semibold hover:bg-orange-700">Masuk untuk mendaftar</a>
            @endauth
        </aside>
    </div>
</x-app-layout>
