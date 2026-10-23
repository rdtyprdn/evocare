<x-app-layout>
    <x-slot name="header">
        @include('organizer.events._header', ['title' => 'Kelola Jadwal Kegiatan'])
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <!-- Indikator Bentrok Jadwal -->
        @if (count($conflictPairs) > 0)
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg>
                    <div class="space-y-1">
                        <p class="font-semibold">Bentrok Jadwal Terdeteksi ({{ count($conflictPairs) }} pasang kegiatan beririsan waktu)</p>
                        <p class="text-xs text-amber-800">Beberapa kegiatan aktif memiliki rentang waktu pelaksanaan yang saling bertumpukan:</p>
                        <ul class="mt-2 space-y-1 text-xs">
                            @foreach ($conflictPairs as [$evA, $evB])
                                <li>
                                    <strong>{{ $evA->title }}</strong> ({{ $evA->start_at->format('d M Y H:i') }} - {{ $evA->end_at->format('H:i') }})
                                    beririsan dengan
                                    <strong>{{ $evB->title }}</strong> ({{ $evB->start_at->format('d M Y H:i') }} - {{ $evB->end_at->format('H:i') }})
                                    &mdash;
                                    <a href="{{ route('organizer.events.edit', $evA) }}" class="text-orange-700 underline font-medium">Sesuaikan Jadwal</a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @else
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900 flex items-center gap-3">
                <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                <div>
                    <p class="font-semibold">Jadwal Teratur (Tidak Ada Bentrok Jadwal)</p>
                    <p class="text-xs text-emerald-800">Seluruh kegiatan aktif terjadwal dengan baik tanpa irisan waktu pelaksanaan.</p>
                </div>
            </div>
        @endif

        <!-- Filter Jadwal -->
        <form method="GET" action="{{ route('organizer.schedule') }}" class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex flex-col sm:flex-row sm:items-end gap-4">
            <div class="flex-1">
                <label for="period" class="block text-xs font-medium text-gray-600">Periode Pelaksanaan</label>
                <select id="period" name="period" class="mt-1 w-full rounded-md border-gray-300 text-sm">
                    <option value="all" @selected($period === 'all')>Semua Waktu ({{ $stats['total_events'] }})</option>
                    <option value="upcoming" @selected($period === 'upcoming')>Akan Datang</option>
                    <option value="ongoing" @selected($period === 'ongoing')>Sedang Berlangsung</option>
                    <option value="completed" @selected($period === 'completed')>Selesai</option>
                </select>
            </div>
            <div class="flex-1">
                <label for="month" class="block text-xs font-medium text-gray-600">Bulan Pelaksanaan</label>
                <select id="month" name="month" class="mt-1 w-full rounded-md border-gray-300 text-sm">
                    <option value="">Semua Bulan</option>
                    @foreach ($availableMonths as $ym => $label)
                        <option value="{{ $ym }}" @selected($month === $ym)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex-1">
                <label for="status" class="block text-xs font-medium text-gray-600">Status Publikasi</label>
                <select id="status" name="status" class="mt-1 w-full rounded-md border-gray-300 text-sm">
                    <option value="all" @selected($status === 'all')>Semua Status</option>
                    @foreach (\App\Models\Event::STATUSES as $val => $label)
                        <option value="{{ $val }}" @selected($status === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="rounded-md bg-orange-600 px-4 py-2 text-sm font-medium text-white hover:bg-orange-700">Terapkan Filter</button>
                @if ($period !== 'all' || $month !== '' || $status !== 'all')
                    <a href="{{ route('organizer.schedule') }}" class="rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-600 hover:bg-gray-50">Reset</a>
                @endif
            </div>
        </form>

        <!-- Linimasa Agenda per Bulan -->
        <div class="space-y-6">
            @forelse ($groupedByMonth as $ym => $monthEvents)
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="bg-gray-50 px-5 py-3 border-b border-gray-100 flex items-center justify-between">
                        <h3 class="font-semibold text-sm text-gray-900">
                            {{ $availableMonths[$ym] ?? $monthEvents->first()->start_at?->translatedFormat('F Y') ?? 'Jadwal Kegiatan' }}
                        </h3>
                        <span class="text-xs text-gray-500">{{ $monthEvents->count() }} kegiatan</span>
                    </div>
                    <div class="divide-y divide-gray-100">
                        @foreach ($monthEvents as $event)
                            @php
                                $execState = $event->executionState();
                                $hasConflict = ! empty($conflicts[$event->id]);
                            @endphp
                            <div class="p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-gray-50/50">
                                <div class="flex items-start gap-4">
                                    <div class="w-16 rounded-lg border border-orange-200 bg-orange-50/60 p-2 text-center shrink-0">
                                        <div class="text-xs uppercase font-semibold text-orange-700">{{ $event->start_at->translatedFormat('M') }}</div>
                                        <div class="text-xl font-bold text-gray-900 leading-tight">{{ $event->start_at->format('d') }}</div>
                                        <div class="text-[11px] text-gray-500">{{ $event->start_at->translatedFormat('D') }}</div>
                                    </div>
                                    <div class="space-y-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <a href="{{ route('events.show', $event) }}" class="font-semibold text-gray-900 hover:text-orange-600">{{ $event->title }}</a>
                                            <span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $execState === 'completed' ? 'bg-gray-100 text-gray-700' : ($execState === 'ongoing' ? 'bg-emerald-50 text-emerald-700' : 'bg-blue-50 text-blue-700') }}">
                                                {{ $event->executionStateLabel() }}
                                            </span>
                                            <span class="rounded-full px-2 py-0.5 text-xs {{ $event->status === 'published' ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-600' }}">
                                                {{ \App\Models\Event::STATUSES[$event->status] }}
                                            </span>
                                            @if ($hasConflict)
                                                <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800">
                                                    Bentrok Jadwal
                                                </span>
                                            @endif
                                        </div>
                                        <div class="text-xs text-gray-600 flex flex-wrap items-center gap-x-4 gap-y-1">
                                            <span>Waktu: <strong>{{ $event->start_at->format('d M Y H:i') }}</strong> s.d. <strong>{{ $event->end_at->format('d M Y H:i') }}</strong></span>
                                            <span>Lokasi: {{ $event->city }} &mdash; {{ $event->location }}</span>
                                            <span>Kategori: {{ $event->category->name }}</span>
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            Kuota Terisi: <strong class="text-gray-800">{{ $event->confirmed_registrations_count }} / {{ $event->quota }}</strong> ({{ $event->occupancyPercentage() }}%)
                                        </div>
                                    </div>
                                </div>
                                <div class="flex flex-wrap items-center gap-2 shrink-0 text-xs font-medium">
                                    <a href="{{ route('organizer.events.edit', $event) }}" class="rounded-md border border-gray-300 px-3 py-1.5 text-gray-700 hover:bg-gray-50">Ubah Jadwal</a>
                                    <a href="{{ route('organizer.events.registrants', $event) }}" class="rounded-md border border-blue-200 bg-blue-50 px-3 py-1.5 text-blue-700 hover:bg-blue-100">Pendaftar</a>
                                    <a href="{{ route('organizer.events.report', $event) }}" class="rounded-md border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-emerald-700 hover:bg-emerald-100">Laporan</a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 text-center text-gray-500">
                    Tidak ada jadwal kegiatan yang sesuai dengan filter yang dipilih.
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>
