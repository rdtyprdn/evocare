@php
    use App\Models\Registration;
    use Illuminate\Support\Str;

    $sisa = $event->remainingQuota();
    $used = max(0, $event->quota - $sisa);
    $percent = $event->occupancyPercentage();
    $org = $event->organization;
    $canManage = auth()->check() && auth()->user()->can('managePhotos', $event);
    $canEdit = auth()->check() && auth()->user()->can('update', $event);
    $isRegistered = $myRegistration && $myRegistration->status === 'confirmed';
    $isFinished = $event->end_at->isPast();
    $canRegister = $event->status === 'published' && $sisa > 0 && $event->end_at->isFuture();
    $state = $event->executionState();
    $stateClass = ['upcoming' => 'bg-blue-50 text-blue-700', 'ongoing' => 'bg-green-50 text-green-700', 'completed' => 'bg-gray-100 text-gray-600'][$state];
    $icons = [
        'cal' => 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5',
        'clock' => 'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
        'pin' => 'M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0ZM19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z',
        'tag' => 'M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3ZM6 6h.008v.008H6V6Z',
        'org' => 'M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21',
        'users' => 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z',
        'check' => 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
        'chev' => 'm8.25 4.5 7.5 7.5-7.5 7.5',
        'pencil' => 'm16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125',
    ];
    $rows = [
        ['cal', 'Tanggal', $event->start_at->translatedFormat('l, d F Y')],
        ['clock', 'Waktu', $event->start_at->format('H:i').' – '.$event->end_at->format('d M Y H:i')],
        ['pin', 'Lokasi', $event->location.', '.$event->city],
        ['tag', 'Kategori', $event->category->name],
        ['org', 'Penyelenggara', $org?->name ?? ($event->creator?->name ?? 'Penyelenggara')],
    ];
@endphp
<x-app-layout>
    <x-slot name="title">{{ $event->title }}</x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6 pb-28 lg:pb-12 space-y-6">
        {{-- Breadcrumb --}}
        <nav aria-label="Breadcrumb" class="flex items-center gap-1.5 text-sm text-gray-500 min-w-0">
            <a href="{{ route('home') }}" class="hover:text-orange-600 shrink-0">Jelajahi Kegiatan</a>
            <svg class="w-3 h-3 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons['chev'] }}"/></svg>
            <a href="{{ route('home', ['category' => $event->category->slug]) }}" class="hover:text-orange-600 shrink-0">{{ $event->category->name }}</a>
            <svg class="w-3 h-3 shrink-0 text-gray-400 hidden sm:block" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons['chev'] }}"/></svg>
            <span class="hidden sm:block truncate font-medium text-gray-800">{{ $event->title }}</span>
        </nav>

        {{-- Judul --}}
        <header class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-3">
            <div class="space-y-2.5 min-w-0">
                <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-gray-900">{{ $event->title }}</h1>
                <div class="text-gray-600 flex items-center gap-x-2 gap-y-1 flex-wrap text-sm sm:text-base min-w-0">
                    <span class="text-gray-500">Diselenggarakan oleh</span> <x-event-organizer :event="$event" />
                    @if ($org)<span class="text-sm text-gray-400">({{ $org->typeLabel() }})</span>@endif
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2 shrink-0">
                @if ($event->status !== 'published')
                    <span class="inline-block px-2.5 py-1 text-xs font-medium rounded-full bg-gray-200 text-gray-700">{{ \App\Models\Event::STATUSES[$event->status] }}</span>
                @endif
                <span class="px-2.5 py-1 text-xs font-medium rounded-full {{ $stateClass }}">{{ $event->executionStateLabel() }}</span>
                <span class="px-2.5 py-1 text-xs font-semibold uppercase rounded-full bg-orange-50 text-orange-700">{{ $event->category->name }}</span>
                @if ($canEdit)
                    <a href="{{ route('organizer.events.edit', $event) }}" class="inline-flex items-center gap-1.5 rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 transition">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons['pencil'] }}"/></svg>
                        Ubah kegiatan
                    </a>
                @endif
                @if ($canManage)
                    <a href="{{ route('organizer.events.edit', $event) }}#galeri" class="rounded-md px-2 py-1.5 text-xs font-semibold text-orange-600 hover:underline">Kelola galeri</a>
                @endif
            </div>
        </header>

        {{-- Area utama --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2 min-w-0">
                <x-event-media-viewer :event="$event" :can-manage="$canManage" />
            </div>

            <aside class="space-y-4 min-w-0">
                <div class="hidden lg:block relative aspect-[2/1] overflow-hidden rounded-xl bg-gray-100">
                    <x-event-image :event="$event" class="absolute inset-0 h-full object-center" />
                </div>
                <p class="text-sm text-gray-700 leading-relaxed">{{ Str::limit($event->description, 220) }}</p>

                <div class="rounded-xl border border-gray-200 bg-white p-4 space-y-3">
                    @foreach ($rows as [$icon, $label, $value])
                        <div class="flex gap-2.5 text-sm">
                            <svg class="w-4 h-4 mt-0.5 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons[$icon] }}"/></svg>
                            <span class="w-24 shrink-0 text-gray-500">{{ $label }}</span>
                            <span class="font-medium text-gray-800 min-w-0 break-words">{{ $value }}</span>
                        </div>
                    @endforeach
                    <div class="pt-1 space-y-1.5">
                        <div class="flex items-center justify-between text-sm">
                            <span class="flex items-center gap-2.5 text-gray-500">
                                <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons['users'] }}"/></svg>
                                Sisa kuota
                            </span>
                            <span><strong class="{{ $sisa ? 'text-green-700' : 'text-red-600' }}">{{ $sisa }}</strong> dari {{ $event->quota }}</span>
                        </div>
                        <div class="h-2 rounded-full bg-gray-100 overflow-hidden" role="progressbar" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100" aria-label="Kuota terisi">
                            <div class="h-full rounded-full bg-orange-500" style="width: {{ max($used ? 3 : 0, $percent) }}%"></div>
                        </div>
                        <p class="text-xs text-gray-400">{{ $used }} dari {{ $event->quota }} tempat terisi</p>
                    </div>
                </div>

                <div class="space-y-2">
                    <p class="text-xs font-semibold text-gray-500">Bisa ikut sebagai</p>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach (Registration::TYPES as $label)
                            <span class="rounded-full border border-gray-200 bg-white px-2.5 py-1 text-xs font-medium text-gray-700">{{ $label }}</span>
                        @endforeach
                        <span class="rounded-full bg-orange-50 px-2.5 py-1 text-xs font-medium text-orange-700">{{ $event->category->name }}</span>
                    </div>
                </div>
            </aside>
        </div>

        {{-- Bar pendaftaran --}}
        <section id="daftar" aria-label="Pendaftaran" class="scroll-mt-24">
            @auth
                @if ($isRegistered)
                    <div class="rounded-xl border border-green-200 bg-green-50 p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div class="flex items-start gap-3">
                            <svg class="w-6 h-6 shrink-0 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons['check'] }}"/></svg>
                            <div>
                                <p class="font-bold text-gray-900">Anda terdaftar sebagai <strong>{{ Registration::TYPES[$myRegistration->type] }}</strong>.</p>
                                <p class="text-sm text-gray-600">Sampai jumpa di {{ $event->city }}, {{ $event->start_at->translatedFormat('l, d F Y') }} pukul {{ $event->start_at->format('H:i') }}.</p>
                            </div>
                        </div>
                        <x-cancel-registration-modal
                            :registration="$myRegistration"
                            button-label="Batalkan Pendaftaran"
                            button-class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-red-300 bg-white px-4 py-2.5 text-sm font-semibold text-red-600 hover:bg-red-50 transition" />
                    </div>
                @elseif ($canRegister)
                    <form method="POST" action="{{ route('registrations.store', $event) }}" class="rounded-xl border border-orange-100 bg-orange-50 p-4 sm:p-5 grid gap-4 lg:grid-cols-[1fr_auto] lg:items-center">
                        @csrf
                        <div>
                            <p class="text-lg font-bold text-gray-900">Ikut {{ $event->title }}</p>
                            <p class="text-sm text-gray-600">Pilih peranmu lalu daftar, sisa {{ $sisa }} tempat.</p>
                        </div>
                        <div class="flex flex-col sm:flex-row gap-2.5 sm:items-start">
                            <label class="sr-only" for="reg-type">Daftar sebagai</label>
                            <div class="flex items-center rounded-lg border border-gray-300 bg-white pl-3">
                                <span class="text-sm text-gray-500 whitespace-nowrap">Daftar sebagai</span>
                                <select id="reg-type" name="type" class="border-0 bg-transparent text-sm font-semibold text-gray-900 focus:ring-0">
                                    @foreach (Registration::TYPES as $val => $label)
                                        <option value="{{ $val }}" @selected(old('type') === $val)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <label class="sr-only" for="reg-note">Catatan</label>
                            <textarea id="reg-note" name="note" rows="1" placeholder="Catatan (opsional)" class="sm:w-56 rounded-lg border-gray-300 text-sm">{{ old('note') }}</textarea>
                            <button class="rounded-lg bg-orange-600 px-7 py-2.5 text-white font-bold hover:bg-orange-700 transition whitespace-nowrap">Daftar Sekarang</button>
                        </div>
                        <x-input-error :messages="$errors->get('type')" class="lg:col-span-2" />
                    </form>
                @else
                    <div class="rounded-xl border border-gray-200 bg-gray-100 p-4 sm:p-5 text-sm text-gray-600">
                        @if ($isFinished)
                            <p class="font-semibold text-gray-800">Kegiatan ini sudah selesai.</p>
                        @elseif ($sisa <= 0)
                            <p class="font-semibold text-gray-800">Kuota sudah penuh.</p>
                        @endif
                        <p>Pendaftaran tidak tersedia (kuota penuh atau kegiatan ditutup).</p>
                    </div>
                @endif
            @else
                <div class="rounded-xl border border-orange-100 bg-orange-50 p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <p class="text-lg font-bold text-gray-900">Ikut {{ $event->title }}</p>
                        <p class="text-sm text-gray-600">Masuk dulu untuk memilih peran dan mendaftar.</p>
                    </div>
                    <a href="{{ route('login', ['redirect' => route('events.show', $event, false)]) }}" class="text-center rounded-lg bg-orange-600 px-7 py-2.5 text-white font-bold hover:bg-orange-700 transition">Masuk untuk mendaftar</a>
                </div>
            @endauth
        </section>

        {{-- Bawah --}}
        <div class="grid gap-6 lg:grid-cols-3">
            <section class="lg:col-span-2 rounded-xl border border-gray-200 bg-white p-5 sm:p-6 space-y-3">
                <h2 class="text-lg font-bold text-gray-900">Tentang Kegiatan</h2>
                <div class="prose max-w-none whitespace-pre-line text-gray-700">{{ $event->description }}</div>
            </section>
            <section class="rounded-xl border border-gray-200 bg-white p-5 sm:p-6 space-y-3 h-fit">
                <p class="text-xs font-semibold text-gray-500">Penyelenggara</p>
                <div class="flex items-center gap-3">
                    @if ($org)
                        <x-org-logo :org="$org" class="w-12 h-12 text-base" />
                        <div class="space-y-1">
                            <p class="font-bold text-gray-900">{{ $org->name }}</p>
                            @if ($org->isVerified())<x-verified-badge />@endif
                        </div>
                    @else
                        <div class="space-y-1">
                            <p class="font-bold text-gray-900">{{ $event->creator?->name ?? 'Penyelenggara' }}</p>
                            <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-500">Organisasi belum terverifikasi</span>
                        </div>
                    @endif
                </div>
                @if ($org)
                    <p class="text-sm text-gray-600">{{ $org->typeLabel() }}{{ $org->city ? ', '.$org->city : '' }}.</p>
                @endif
            </section>
        </div>
    </div>

    {{-- Bar daftar melekat untuk layar kecil --}}
    @if (! $isRegistered && ($canRegister || ! auth()->check()))
        <div class="lg:hidden fixed inset-x-0 bottom-0 z-40 border-t border-gray-200 bg-white/95 backdrop-blur px-4 pt-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] shadow-[0_-4px_12px_rgba(0,0,0,0.06)]">
            <div class="flex items-center gap-3">
                <div class="min-w-0 flex-1">
                    <p class="text-xs text-gray-500">Sisa {{ $sisa }} dari {{ $event->quota }} tempat</p>
                    <p class="truncate text-sm font-semibold text-gray-900">{{ $event->start_at->translatedFormat('d M Y') }}, {{ $event->city }}</p>
                </div>
                @auth
                    <a href="#daftar" class="shrink-0 rounded-lg bg-orange-600 px-6 py-2.5 text-sm font-bold text-white hover:bg-orange-700">Daftar Sekarang</a>
                @else
                    <a href="{{ route('login', ['redirect' => route('events.show', $event, false)]) }}" class="shrink-0 rounded-lg bg-orange-600 px-6 py-2.5 text-sm font-bold text-white hover:bg-orange-700">Masuk untuk daftar</a>
                @endauth
            </div>
        </div>
    @endif
</x-app-layout>
