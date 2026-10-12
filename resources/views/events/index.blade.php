<x-app-layout>
    <section class="bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-10 pb-6">
            <div class="rounded-2xl bg-gradient-to-r from-gray-900 via-gray-800 to-orange-900 text-white px-6 sm:px-12 py-12">
                <h1 class="text-3xl sm:text-5xl font-extrabold tracking-tight max-w-2xl">Temukan kegiatan sosial di sekitarmu</h1>
                <p class="mt-3 text-gray-200 max-w-xl">Jadi relawan, donatur, atau peserta dalam aksi pendidikan, kesehatan, lingkungan, ekonomi, hingga tanggap bencana.</p>
                <form method="GET" action="{{ route('home') }}" class="mt-8">
                    @if (!empty($filters['category']))<input type="hidden" name="category" value="{{ $filters['category'] }}">@endif
                    <div class="flex items-center bg-white rounded-full shadow-lg pl-5 pr-2 py-2 text-gray-800 max-w-3xl">
                        <svg class="w-5 h-5 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                        <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Cari kegiatan" class="flex-1 min-w-0 border-0 focus:ring-0 text-sm">
                        <span class="h-6 w-px bg-gray-200 mx-2"></span>
                        <svg class="w-5 h-5 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>
                        <div class="relative w-36 sm:w-56" x-data="cityAutocomplete(@js($filters['city'] ?? ''), @js(route('api.cities')))" @click.outside="close()" @keydown.escape.window="close()">
                            <input type="hidden" name="city" :value="city">
                            <input type="text" x-model="text" @input.debounce.200ms="onInput()"
                                   @keydown.arrow-down.prevent="move(1)" @keydown.arrow-up.prevent="move(-1)" @keydown.enter="enter($event)"
                                   placeholder="Semua kota" autocomplete="off" role="combobox" aria-autocomplete="list" :aria-expanded="open" aria-controls="city-listbox"
                                   class="w-full border-0 focus:ring-0 text-sm bg-transparent px-2">
                            <ul id="city-listbox" x-show="open && items.length" x-cloak role="listbox"
                                class="absolute left-0 top-full mt-4 w-72 bg-white rounded-xl shadow-xl ring-1 ring-black/5 py-2 z-50 text-left">
                                <template x-for="(item, i) in items" :key="item.name + i">
                                    <li role="option" :aria-selected="i === active" @mouseenter="active = i" @mousedown.prevent="select(item)"
                                        :class="i === active ? 'bg-gray-100' : ''" class="px-4 py-3 cursor-pointer">
                                        <div class="text-sm font-medium text-gray-900" x-text="item.name"></div>
                                        <div class="text-xs text-gray-500 mt-0.5" x-text="item.subtitle"></div>
                                    </li>
                                </template>
                            </ul>
                        </div>
                        <button class="ml-1 shrink-0 w-10 h-10 rounded-full bg-orange-600 hover:bg-orange-700 flex items-center justify-center" aria-label="Cari">
                            <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                        </button>
                    </div>
                    <div class="mt-4 flex flex-wrap items-center gap-2 text-sm text-gray-200">
                        <span>Tanggal:</span>
                        <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="rounded-full border-0 text-gray-800 text-sm py-1" title="Dari tanggal">
                        <span>s.d.</span>
                        <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="rounded-full border-0 text-gray-800 text-sm py-1" title="Sampai tanggal">
                        @if (array_filter($filters))<a href="{{ route('home') }}" class="ml-2 underline hover:text-white">Reset filter</a>@endif
                    </div>
                </form>
            </div>
        </div>
    </section>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        @php $active = $filters['category'] ?? ''; @endphp
        <nav aria-label="Kategori" class="flex gap-6 sm:gap-10 overflow-x-auto pb-4 sm:justify-between snap-x">
            @php $items = collect([['slug' => '', 'icon' => 'semua', 'name' => 'Semua']])->concat($categories->map(fn ($c) => ['slug' => $c->slug, 'icon' => $c->slug, 'name' => $c->name])); @endphp
            @foreach ($items as $item)
                @php $isActive = $active === $item['slug']; $params = array_filter(array_merge($filters, ['category' => $item['slug'] ?: null, 'page' => null])); @endphp
                <a href="{{ route('home', $params) }}" class="group snap-start shrink-0 w-24 flex flex-col items-center text-center" @if($isActive) aria-current="page" @endif>
                    <span class="w-20 h-20 rounded-full border flex items-center justify-center transition {{ $isActive ? 'bg-indigo-100 border-indigo-100 text-indigo-700' : 'bg-white border-gray-200 text-gray-700 group-hover:bg-indigo-100 group-hover:border-indigo-100 group-hover:text-indigo-700' }}">
                        <x-category-icon :slug="$item['icon']" class="w-8 h-8" />
                    </span>
                    <span class="mt-3 text-xs font-semibold leading-tight {{ $isActive ? 'text-indigo-700' : 'text-gray-800 group-hover:text-indigo-700' }}">{{ $item['name'] }}</span>
                </a>
            @endforeach
        </nav>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-8">
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
