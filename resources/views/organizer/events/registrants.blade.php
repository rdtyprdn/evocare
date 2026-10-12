<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">Pendaftar: {{ $event->title }}</h2>
        <p class="text-sm text-gray-500">{{ $registrations->where('status', 'confirmed')->count() }} terkonfirmasi dari kuota {{ $event->quota }}</p></x-slot>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white rounded-xl shadow-sm overflow-x-auto">
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
