<x-app-layout>
    <x-slot name="header">
        @include('organizer.events._header', ['title' => 'Dashboard Penyelenggara'])
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <!-- Ringkasan Statistik Kegiatan & Pendaftar -->
        <section aria-label="Statistik Kegiatan dan Pendaftar" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">Total Kegiatan</span>
                    <span class="p-2 rounded-lg bg-orange-50 text-orange-600">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/></svg>
                    </span>
                </div>
                <div class="mt-3 text-3xl font-bold text-gray-900">{{ $stats['total_events'] }}</div>
                <div class="mt-2 flex flex-wrap gap-x-3 gap-y-1 text-xs text-gray-600">
                    <span>Aktif/Akan Datang: <strong class="text-gray-900">{{ $stats['upcoming_events'] }}</strong></span>
                    <span>Selesai: <strong class="text-gray-900">{{ $stats['completed_events'] }}</strong></span>
                    @if ($stats['draft_cancelled_events'] > 0)
                        <span>Draf/Batal: <strong class="text-gray-900">{{ $stats['draft_cancelled_events'] }}</strong></span>
                    @endif
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">Pendaftar Terkonfirmasi</span>
                    <span class="p-2 rounded-lg bg-emerald-50 text-emerald-600">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/></svg>
                    </span>
                </div>
                <div class="mt-3 text-3xl font-bold text-gray-900">{{ $stats['confirmed_registrations'] }} <span class="text-sm font-normal text-gray-500">/ {{ $stats['total_quota'] }} kuota</span></div>
                <div class="mt-2 text-xs text-gray-600">
                    Sisa kuota keseluruhan: <strong class="text-gray-900">{{ max(0, $stats['total_quota'] - $stats['confirmed_registrations']) }}</strong>
                    @if ($stats['cancelled_registrations'] > 0)
                        <span class="ml-2 text-red-600">({{ $stats['cancelled_registrations'] }} dibatalkan)</span>
                    @endif
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">Keterisian Kuota</span>
                    <span class="p-2 rounded-lg bg-blue-50 text-blue-600">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6a7.5 7.5 0 1 0 7.5 7.5h-7.5V6Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5H21A7.5 7.5 0 0 0 13.5 3v7.5Z"/></svg>
                    </span>
                </div>
                <div class="mt-3 text-3xl font-bold text-gray-900">{{ $stats['occupancy_rate'] }}%</div>
                <div class="mt-3 w-full bg-gray-100 rounded-full h-2 overflow-hidden">
                    <div class="bg-orange-600 h-2 rounded-full" style="width: {{ $stats['occupancy_rate'] }}%"></div>
                </div>
                <p class="mt-1.5 text-xs text-gray-500">Rata-rata keterisian dari seluruh kegiatan</p>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">Peran Partisipan</span>
                    <span class="p-2 rounded-lg bg-purple-50 text-purple-600">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z"/></svg>
                    </span>
                </div>
                <div class="mt-3 space-y-1.5 text-xs">
                    <div class="flex justify-between text-gray-700">
                        <span>Relawan</span>
                        <span class="font-semibold text-gray-900">{{ $stats['roles']['relawan'] }}</span>
                    </div>
                    <div class="flex justify-between text-gray-700">
                        <span>Donatur</span>
                        <span class="font-semibold text-gray-900">{{ $stats['roles']['donatur'] }}</span>
                    </div>
                    <div class="flex justify-between text-gray-700">
                        <span>Peserta</span>
                        <span class="font-semibold text-gray-900">{{ $stats['roles']['peserta'] }}</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Tabel Daftar Kegiatan -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <div>
                    <h3 class="font-semibold text-gray-900">Daftar Kegiatan Organisasi</h3>
                    <p class="text-xs text-gray-500">Kelola kegiatan, pantau kuota partisipan, jadwal pelaksanaan, dan laporan kegiatan.</p>
                </div>
                <div class="flex items-center gap-3 text-xs font-medium">
                    <a href="{{ route('organizer.schedule') }}" class="text-orange-600 hover:underline">Lihat Linimasa Jadwal</a>
                    <span class="text-gray-300">|</span>
                    <a href="{{ route('organizer.reports') }}" class="text-orange-600 hover:underline">Rekap Laporan Kegiatan</a>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-left text-gray-600">
                        <tr>
                            <th class="p-3">Kegiatan</th>
                            <th class="p-3">Kategori</th>
                            <th class="p-3">Tanggal</th>
                            <th class="p-3">Pendaftar & Kuota</th>
                            <th class="p-3">Status</th>
                            <th class="p-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($events as $event)
                        @php
                            $occ = $event->occupancyPercentage();
                            $execState = $event->executionState();
                        @endphp
                        <tr class="border-t hover:bg-gray-50/60">
                            <td class="p-3">
                                <a href="{{ route('events.show', $event) }}" class="font-medium text-gray-900 hover:text-orange-600">{{ $event->title }}</a>
                                <div class="text-xs text-gray-500 flex items-center gap-2 mt-0.5">
                                    <span>{{ $event->city }}</span>
                                    <span>&bull;</span>
                                    <span class="{{ $execState === 'completed' ? 'text-gray-500' : ($execState === 'ongoing' ? 'text-emerald-700 font-medium' : 'text-blue-700') }}">{{ $event->executionStateLabel() }}</span>
                                </div>
                            </td>
                            <td class="p-3">
                                {{ $event->category->name }}
                                @unless ($event->organization)
                                    <div class="text-xs text-amber-700">Belum terhubung organisasi</div>
                                @endunless
                            </td>
                            <td class="p-3 whitespace-nowrap">
                                <div>{{ $event->start_at->format('d M Y H:i') }}</div>
                                <div class="text-xs text-gray-400">s.d. {{ $event->end_at->format('d M Y H:i') }}</div>
                            </td>
                            <td class="p-3 min-w-[180px]">
                                <div class="flex items-center justify-between text-xs font-medium">
                                    <span>{{ $event->confirmed_registrations_count }} / {{ $event->quota }}</span>
                                    <span class="text-gray-500">{{ $occ }}%</span>
                                </div>
                                <div class="mt-1 w-full bg-gray-100 rounded-full h-1.5 overflow-hidden">
                                    <div class="bg-orange-600 h-1.5 rounded-full" style="width: {{ $occ }}%"></div>
                                </div>
                                <div class="mt-1 text-[11px] text-gray-500">
                                    R: {{ $event->relawan_count ?? 0 }} &middot; D: {{ $event->donatur_count ?? 0 }} &middot; P: {{ $event->peserta_count ?? 0 }}
                                </div>
                            </td>
                            <td class="p-3 whitespace-nowrap">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $event->status === 'published' ? 'bg-emerald-50 text-emerald-700' : ($event->status === 'draft' ? 'bg-gray-100 text-gray-700' : 'bg-red-50 text-red-700') }}">
                                    {{ \App\Models\Event::STATUSES[$event->status] }}
                                </span>
                            </td>
                            <td class="p-3 whitespace-nowrap space-x-2">
                                <a href="{{ route('organizer.events.registrants', $event) }}" class="text-blue-600 hover:underline">Pendaftar</a>
                                <a href="{{ route('organizer.events.report', $event) }}" class="text-emerald-700 hover:underline">Laporan</a>
                                <a href="{{ route('organizer.events.edit', $event) }}" class="text-orange-600 hover:underline">Ubah</a>
                                <x-confirm-delete-modal :action="route('organizer.events.destroy', $event)" title="Hapus Kegiatan Sosial?" description="Kegiatan yang dihapus beserta gambar sampul dan data pendaftarannya tidak dapat dikembalikan." :item-name="$event->title" :item-meta="$event->city.' · '.$event->start_at->format('d M Y H:i')" trigger-label="Hapus" confirm-label="Ya, Hapus Kegiatan" cancel-label="Batal" />
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="p-6 text-center text-gray-500">Belum ada kegiatan.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-4">{{ $events->links() }}</div>
    </div>
</x-app-layout>
