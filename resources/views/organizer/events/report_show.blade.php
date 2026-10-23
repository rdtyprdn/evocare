<x-app-layout>
    <x-slot name="header">
        @include('organizer.events._header', ['title' => 'Laporan Kegiatan: '.$event->title])
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <!-- Bilah Aksi Laporan -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 print:hidden">
            <a href="{{ route('organizer.reports') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-600 hover:text-orange-600">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/></svg>
                <span>Kembali ke Rekapitulasi Laporan</span>
            </a>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('organizer.events.registrants', $event) }}" class="rounded-md border border-gray-300 bg-white px-3.5 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50">
                    Kelola Pendaftar
                </a>
                <a href="{{ route('organizer.events.registrants.export', $event) }}" class="inline-flex items-center gap-1.5 rounded-md bg-blue-600 px-3.5 py-2 text-xs font-medium text-white hover:bg-blue-700">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                    <span>Unduh Laporan CSV</span>
                </a>
                <button type="button" onclick="window.print()" class="inline-flex items-center gap-1.5 rounded-md bg-orange-600 px-3.5 py-2 text-xs font-medium text-white hover:bg-orange-700">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z"/></svg>
                    <span>Cetak Laporan</span>
                </button>
            </div>
        </div>

        <!-- Informasi Utama Kegiatan -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 border-b border-gray-100 pb-4">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-wider text-orange-600">{{ $event->category->name }}</div>
                    <h3 class="text-2xl font-bold text-gray-900 mt-0.5">{{ $event->title }}</h3>
                    <p class="text-sm text-gray-600 mt-1">
                        Penyelenggara: <strong>{{ $event->organization?->name ?? Auth::user()->name }}</strong>
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <span class="rounded-full px-3 py-1 text-xs font-medium {{ $event->executionState() === 'completed' ? 'bg-gray-100 text-gray-700' : 'bg-blue-50 text-blue-700' }}">
                        {{ $event->executionStateLabel() }}
                    </span>
                    <span class="rounded-full px-3 py-1 text-xs font-medium {{ $event->status === 'published' ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-700' }}">
                        {{ \App\Models\Event::STATUSES[$event->status] }}
                    </span>
                </div>
            </div>

            <div class="grid sm:grid-cols-3 gap-4 text-sm">
                <div>
                    <div class="text-xs text-gray-500">Waktu Pelaksanaan</div>
                    <div class="font-medium text-gray-900 mt-0.5">{{ $event->start_at->format('d M Y H:i') }} &ndash; {{ $event->end_at->format('d M Y H:i') }}</div>
                </div>
                <div>
                    <div class="text-xs text-gray-500">Kota & Lokasi</div>
                    <div class="font-medium text-gray-900 mt-0.5">{{ $event->city }} &mdash; {{ $event->location }}</div>
                </div>
                <div>
                    <div class="text-xs text-gray-500">Keterisian Kuota</div>
                    <div class="font-medium text-gray-900 mt-0.5">{{ $reportStats['confirmed'] }} terkonfirmasi dari {{ $reportStats['quota'] }} kuota ({{ $reportStats['occupancy'] }}%)</div>
                </div>
            </div>
        </div>

        <!-- Kartu Metrik Laporan Kegiatan -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                <div class="text-xs text-gray-500">Kuota Kegiatan</div>
                <div class="mt-1 text-2xl font-bold text-gray-900">{{ $reportStats['quota'] }}</div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                <div class="text-xs text-gray-500">Terkonfirmasi</div>
                <div class="mt-1 text-2xl font-bold text-emerald-700">{{ $reportStats['confirmed'] }}</div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                <div class="text-xs text-gray-500">Relawan</div>
                <div class="mt-1 text-2xl font-bold text-gray-900">{{ $reportStats['roles']['relawan'] }}</div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                <div class="text-xs text-gray-500">Donatur</div>
                <div class="mt-1 text-2xl font-bold text-gray-900">{{ $reportStats['roles']['donatur'] }}</div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                <div class="text-xs text-gray-500">Peserta</div>
                <div class="mt-1 text-2xl font-bold text-gray-900">{{ $reportStats['roles']['peserta'] }}</div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                <div class="text-xs text-gray-500">Dibatalkan</div>
                <div class="mt-1 text-2xl font-bold text-red-600">{{ $reportStats['cancelled'] }}</div>
            </div>
        </div>

        <!-- Catatan & Evaluasi Laporan Pelaksanaan Kegiatan -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <div>
                    <h4 class="font-semibold text-gray-900">Catatan & Evaluasi Pelaksanaan Kegiatan</h4>
                    <p class="text-xs text-gray-500">Tuliskan ringkasan jalannya kegiatan, capaian penyaluran bantuan, serta evaluasi pasca-acara.</p>
                </div>
                @if ($event->reportUpdatedAt())
                    <span class="text-xs text-gray-500">Terakhir diperbarui: {{ $event->reportUpdatedAt()->format('d M Y H:i') }}</span>
                @endif
            </div>

            <form method="POST" action="{{ route('organizer.events.report.update', $event) }}" class="space-y-3 print:hidden">
                @csrf
                @method('PUT')
                <div>
                    <textarea id="report_summary" name="report_summary" rows="5"
                              placeholder="Contoh: Kegiatan berjalan lancar dengan kehadiran relawan dan peserta sesuai jadwal. Pemeriksaan kesehatan dan penyaluran bantuan telah diselesaikan..."
                              class="w-full rounded-lg border-gray-300 text-sm">{{ old('report_summary', $event->reportSummary()) }}</textarea>
                    <x-input-error :messages="$errors->get('report_summary')" class="mt-1" />
                </div>
                <div class="flex justify-end">
                    <x-primary-button>Simpan Catatan Laporan</x-primary-button>
                </div>
            </form>

            @if (filled($event->reportSummary()))
                <div class="hidden print:block text-sm text-gray-800 whitespace-pre-line border-t pt-3">{{ $event->reportSummary() }}</div>
            @endif
        </div>

        <!-- Tabel Rekapitulasi Pendaftar pada Laporan -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h4 class="font-semibold text-gray-900">Daftar Partisipan Kegiatan ({{ $registrations->count() }})</h4>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-left text-gray-600">
                        <tr>
                            <th class="p-3">No</th>
                            <th class="p-3">Nama</th>
                            <th class="p-3">Email</th>
                            <th class="p-3">Peran</th>
                            <th class="p-3">Status</th>
                            <th class="p-3">Catatan</th>
                            <th class="p-3">Tanggal Daftar</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($registrations as $idx => $r)
                        <tr class="border-t">
                            <td class="p-3 text-gray-500">{{ $idx + 1 }}</td>
                            <td class="p-3 font-medium text-gray-900">{{ $r->user->name }}</td>
                            <td class="p-3 text-gray-600">{{ $r->user->email }}</td>
                            <td class="p-3">{{ \App\Models\Registration::TYPES[$r->type] }}</td>
                            <td class="p-3 {{ $r->status === 'confirmed' ? 'text-green-700 font-medium' : 'text-red-600' }}">{{ $r->status === 'confirmed' ? 'Terkonfirmasi' : 'Dibatalkan' }}</td>
                            <td class="p-3 text-gray-600">{{ $r->note ?: '-' }}</td>
                            <td class="p-3 whitespace-nowrap text-gray-500">{{ $r->created_at->format('d M Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="p-6 text-center text-gray-500">Belum ada pendaftar pada kegiatan ini.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
