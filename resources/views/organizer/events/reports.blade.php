<x-app-layout>
    <x-slot name="header">
        @include('organizer.events._header', ['title' => 'Laporan Kegiatan Penyelenggara'])
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <!-- Ringkasan Rekapitulasi Laporan -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <div class="text-xs font-semibold uppercase tracking-wider text-gray-500">Kegiatan Selesai</div>
                <div class="mt-2 text-2xl font-bold text-gray-900">{{ $stats['completed_events'] }} <span class="text-sm font-normal text-gray-500">/ {{ $stats['total_events'] }} kegiatan</span></div>
                <p class="mt-1 text-xs text-gray-500">Laporan terdokumentasi: <strong>{{ $documentedCount }}</strong></p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <div class="text-xs font-semibold uppercase tracking-wider text-gray-500">Total Partisipan Aktif</div>
                <div class="mt-2 text-2xl font-bold text-gray-900">{{ $stats['confirmed_registrations'] }}</div>
                <p class="mt-1 text-xs text-gray-500">Rata-rata keterisian: <strong>{{ $stats['occupancy_rate'] }}%</strong></p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <div class="text-xs font-semibold uppercase tracking-wider text-gray-500">Rincian Peran Partisipan</div>
                <div class="mt-2 flex items-center gap-3 text-xs text-gray-700">
                    <span>Relawan: <strong class="text-gray-900">{{ $stats['roles']['relawan'] }}</strong></span>
                    <span>Donatur: <strong class="text-gray-900">{{ $stats['roles']['donatur'] }}</strong></span>
                    <span>Peserta: <strong class="text-gray-900">{{ $stats['roles']['peserta'] }}</strong></span>
                </div>
                <p class="mt-1.5 text-xs text-gray-500">Terhitung dari pendaftar terkonfirmasi</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <div class="text-xs font-semibold uppercase tracking-wider text-gray-500">Pembatalan Pendaftaran</div>
                <div class="mt-2 text-2xl font-bold text-gray-900">{{ $stats['cancelled_registrations'] }}</div>
                <p class="mt-1 text-xs text-gray-500">Slot kuota otomatis dikembalikan</p>
            </div>
        </div>

        <!-- Filter Laporan -->
        <form method="GET" action="{{ route('organizer.reports') }}" class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex flex-col sm:flex-row sm:items-end gap-4">
            <div class="flex-1">
                <label for="period" class="block text-xs font-medium text-gray-600">Status Pelaksanaan</label>
                <select id="period" name="period" class="mt-1 w-full rounded-md border-gray-300 text-sm">
                    <option value="all" @selected($period === 'all')>Semua Kegiatan</option>
                    <option value="completed" @selected($period === 'completed')>Selesai Dilaksanakan</option>
                    <option value="ongoing" @selected($period === 'ongoing')>Sedang Berlangsung</option>
                    <option value="upcoming" @selected($period === 'upcoming')>Akan Datang</option>
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
                <button type="submit" class="rounded-md bg-orange-600 px-4 py-2 text-sm font-medium text-white hover:bg-orange-700">Saring Laporan</button>
                @if ($period !== 'all' || $status !== 'all')
                    <a href="{{ route('organizer.reports') }}" class="rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-600 hover:bg-gray-50">Reset</a>
                @endif
            </div>
        </form>

        <!-- Tabel Rekapitulasi Laporan per Kegiatan -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h3 class="font-semibold text-gray-900">Rekapitulasi Laporan per Kegiatan</h3>
                <p class="text-xs text-gray-500">Pilih kegiatan untuk menulis catatan evaluasi pelaksanaan, mencetak laporan, atau mengunduh data pendaftar dalam format CSV.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-left text-gray-600">
                        <tr>
                            <th class="p-3">Kegiatan</th>
                            <th class="p-3">Jadwal & Pelaksanaan</th>
                            <th class="p-3">Kuota Terisi</th>
                            <th class="p-3">Rincian Peran</th>
                            <th class="p-3">Catatan Laporan</th>
                            <th class="p-3">Aksi Laporan</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($events as $event)
                        @php
                            $summaryText = $event->reportSummary();
                        @endphp
                        <tr class="border-t hover:bg-gray-50/60">
                            <td class="p-3">
                                <a href="{{ route('organizer.events.report', $event) }}" class="font-medium text-gray-900 hover:text-orange-600">{{ $event->title }}</a>
                                <div class="text-xs text-gray-500">{{ $event->category->name }} &bull; {{ $event->city }}</div>
                            </td>
                            <td class="p-3 whitespace-nowrap">
                                <div>{{ $event->start_at->format('d M Y H:i') }}</div>
                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $event->executionState() === 'completed' ? 'bg-gray-100 text-gray-700' : 'bg-blue-50 text-blue-700' }}">
                                    {{ $event->executionStateLabel() }}
                                </span>
                            </td>
                            <td class="p-3 whitespace-nowrap">
                                <span class="font-semibold text-gray-900">{{ $event->confirmed_registrations_count }}</span> / {{ $event->quota }}
                                <span class="text-xs text-gray-500">({{ $event->occupancyPercentage() }}%)</span>
                            </td>
                            <td class="p-3 text-xs text-gray-600 whitespace-nowrap">
                                Relawan: <strong>{{ $event->relawan_count ?? 0 }}</strong> &middot;
                                Donatur: <strong>{{ $event->donatur_count ?? 0 }}</strong> &middot;
                                Peserta: <strong>{{ $event->peserta_count ?? 0 }}</strong>
                                @if (($event->cancelled_registrations_count ?? 0) > 0)
                                    <div class="text-red-600">Dibatalkan: {{ $event->cancelled_registrations_count }}</div>
                                @endif
                            </td>
                            <td class="p-3">
                                @if (filled($summaryText))
                                    <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700">Sudah Diisi</span>
                                    <p class="mt-1 text-xs text-gray-500 line-clamp-1 max-w-xs">{{ \Illuminate\Support\Str::limit($summaryText, 60) }}</p>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs text-gray-600">Belum Diisi</span>
                                @endif
                            </td>
                            <td class="p-3 whitespace-nowrap space-x-2">
                                <a href="{{ route('organizer.events.report', $event) }}" class="text-orange-600 font-medium hover:underline">Kelola Laporan</a>
                                <a href="{{ route('organizer.events.registrants.export', $event) }}" class="text-blue-600 hover:underline">Unduh CSV</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="p-6 text-center text-gray-500">Belum ada data kegiatan untuk dilaporkan.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
