<x-app-layout>
    <section class="bg-gradient-to-r from-orange-600 to-amber-500 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <h1 class="text-3xl sm:text-4xl font-bold">Temukan kegiatan sosial di sekitarmu</h1>
            <p class="mt-2 text-orange-50">Jadi relawan, donatur, atau peserta dalam aksi pendidikan, kesehatan, lingkungan, ekonomi, hingga tanggap bencana.</p>
            <form method="GET" action="{{ route('home') }}" class="mt-6 grid grid-cols-1 md:grid-cols-6 gap-3 bg-white p-3 rounded-xl text-gray-800">
                <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Cari kegiatan, mis. donor darah" class="md:col-span-2 rounded-md border-gray-300">
                <select name="category" class="rounded-md border-gray-300">
                    <option value="">Semua kategori</option>
                    @foreach ($categories as $c)
                        <option value="{{ $c->slug }}" @selected(($filters['category'] ?? '') === $c->slug)>{{ $c->name }}</option>
                    @endforeach
                </select>
                <select name="city" class="rounded-md border-gray-300">
                    <option value="">Semua kota</option>
                    @foreach ($cities as $city)
                        <option value="{{ $city }}" @selected(($filters['city'] ?? '') === $city)>{{ $city }}</option>
                    @endforeach
                </select>
                <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="rounded-md border-gray-300" title="Dari tanggal">
                <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="rounded-md border-gray-300" title="Sampai tanggal">
                <div class="md:col-span-6 flex gap-2 justify-end">
                    <a href="{{ route('home') }}" class="px-4 py-2 text-sm text-gray-600">Reset</a>
                    <button class="rounded-md bg-orange-600 px-6 py-2 text-white font-semibold hover:bg-orange-700">Cari</button>
                </div>
            </form>
        </div>
    </section>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-wrap gap-2 mb-6">
            @foreach ($categories as $c)
                <a href="{{ route('home', array_merge($filters, ['category' => $c->slug])) }}" class="px-3 py-1 rounded-full border text-sm {{ ($filters['category'] ?? '') === $c->slug ? 'bg-orange-600 text-white border-orange-600' : 'bg-white hover:border-orange-400' }}">{{ $c->icon }} {{ $c->name }}</a>
            @endforeach
        </div>

        <h2 class="text-xl font-semibold mb-4">Kegiatan mendatang <span class="text-gray-400 text-base">({{ $events->total() }})</span></h2>
        @if ($events->isEmpty())
            <div class="bg-white rounded-xl p-10 text-center text-gray-500">Tidak ada kegiatan yang cocok dengan pencarianmu.</div>
        @else
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($events as $event)
                    @include('events._card')
                @endforeach
            </div>
            <div class="mt-8">{{ $events->links() }}</div>
        @endif
    </div>
</x-app-layout>
