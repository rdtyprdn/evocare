<x-app-layout>
    <x-slot name="header">
        @include('organizer.events._header', ['title' => 'Pendaftar: '.$event->title])
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <p class="text-sm text-gray-600">
                    <strong>{{ $roleCounts['confirmed'] }}</strong> terkonfirmasi dari kuota <strong>{{ $event->quota }}</strong>
                    ({{ $event->occupancyPercentage() }}% terisi)
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('organizer.events.report', $event) }}" class="rounded-md border border-gray-300 bg-white px-3.5 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50">
                    Laporan Kegiatan
                </a>
                <a href="{{ route('organizer.events.registrants.export', [$event, 'type' => $type, 'status' => $status]) }}" class="inline-flex items-center gap-1.5 rounded-md bg-orange-600 px-3.5 py-2 text-xs font-medium text-white hover:bg-orange-700">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                    <span>Unduh CSV Pendaftar</span>
                </a>
            </div>
        </div>

        <!-- Filter Peran & Status Pendaftar -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex flex-wrap gap-2 text-xs font-medium">
                <a href="{{ route('organizer.events.registrants', [$event, 'type' => 'all', 'status' => $status]) }}"
                   class="rounded-full px-3 py-1.5 {{ $type === 'all' ? 'bg-orange-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    Semua Peran ({{ $roleCounts['all'] }})
                </a>
                <a href="{{ route('organizer.events.registrants', [$event, 'type' => 'relawan', 'status' => $status]) }}"
                   class="rounded-full px-3 py-1.5 {{ $type === 'relawan' ? 'bg-orange-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    Relawan ({{ $roleCounts['relawan'] }})
                </a>
                <a href="{{ route('organizer.events.registrants', [$event, 'type' => 'donatur', 'status' => $status]) }}"
                   class="rounded-full px-3 py-1.5 {{ $type === 'donatur' ? 'bg-orange-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    Donatur ({{ $roleCounts['donatur'] }})
                </a>
                <a href="{{ route('organizer.events.registrants', [$event, 'type' => 'peserta', 'status' => $status]) }}"
                   class="rounded-full px-3 py-1.5 {{ $type === 'peserta' ? 'bg-orange-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    Peserta ({{ $roleCounts['peserta'] }})
                </a>
            </div>
            <div class="flex flex-wrap gap-2 text-xs font-medium">
                <a href="{{ route('organizer.events.registrants', [$event, 'type' => $type, 'status' => 'all']) }}"
                   class="rounded-md px-2.5 py-1 {{ $status === 'all' ? 'bg-gray-900 text-white' : 'text-gray-600 hover:bg-gray-100' }}">
                    Semua Status
                </a>
                <a href="{{ route('organizer.events.registrants', [$event, 'type' => $type, 'status' => 'confirmed']) }}"
                   class="rounded-md px-2.5 py-1 {{ $status === 'confirmed' ? 'bg-emerald-700 text-white' : 'text-gray-600 hover:bg-gray-100' }}">
                    Terkonfirmasi ({{ $roleCounts['confirmed'] }})
                </a>
                <a href="{{ route('organizer.events.registrants', [$event, 'type' => $type, 'status' => 'cancelled']) }}"
                   class="rounded-md px-2.5 py-1 {{ $status === 'cancelled' ? 'bg-red-600 text-white' : 'text-gray-600 hover:bg-gray-100' }}">
                    Dibatalkan ({{ $roleCounts['cancelled'] }})
                </a>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-left text-gray-600"><tr><th class="p-3">Nama</th><th class="p-3">Email</th><th class="p-3">Sebagai</th><th class="p-3">Status</th><th class="p-3">Catatan</th><th class="p-3">Tanggal Daftar</th></tr></thead>
                <tbody>
                @forelse ($registrations as $r)
                    <tr class="border-t">
                        <td class="p-3">{{ $r->user->name }}</td><td class="p-3">{{ $r->user->email }}</td>
                        <td class="p-3">{{ \App\Models\Registration::TYPES[$r->type] }}</td>
                        <td class="p-3 {{ $r->status === 'confirmed' ? 'text-green-700' : 'text-red-600' }}">{{ $r->status === 'confirmed' ? 'Terkonfirmasi' : 'Dibatalkan' }}</td>
                        <td class="p-3">{{ $r->note }}</td><td class="p-3">{{ $r->created_at->format('d M Y H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="p-6 text-center text-gray-500">Belum ada pendaftar.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
