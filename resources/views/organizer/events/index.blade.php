<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl">Dashboard Penyelenggara</h2>
            <a href="{{ route('organizer.events.create') }}" class="rounded-md bg-orange-600 px-4 py-2 text-white text-sm hover:bg-orange-700">+ Buat Kegiatan</a>
        </div>
    </x-slot>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white rounded-xl shadow-sm overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-left text-gray-600">
                    <tr><th class="p-3">Kegiatan</th><th class="p-3">Kategori</th><th class="p-3">Tanggal</th><th class="p-3">Pendaftar</th><th class="p-3">Status</th><th class="p-3">Aksi</th></tr>
                </thead>
                <tbody>
                @forelse ($events as $event)
                    <tr class="border-t">
                        <td class="p-3"><a href="{{ route('events.show', $event) }}" class="font-medium hover:text-orange-600">{{ $event->title }}</a><div class="text-gray-500">{{ $event->city }}</div></td>
                        <td class="p-3">{{ $event->category->name }}</td>
                        <td class="p-3">{{ $event->start_at->format('d M Y H:i') }}</td>
                        <td class="p-3">{{ $event->confirmed_registrations_count }} / {{ $event->quota }}</td>
                        <td class="p-3">{{ \App\Models\Event::STATUSES[$event->status] }}</td>
                        <td class="p-3 whitespace-nowrap space-x-2">
                            <a href="{{ route('organizer.events.registrants', $event) }}" class="text-blue-600">Pendaftar</a>
                            <a href="{{ route('organizer.events.edit', $event) }}" class="text-orange-600">Ubah</a>
                            <form method="POST" action="{{ route('organizer.events.destroy', $event) }}" class="inline" onsubmit="return confirm('Hapus kegiatan ini?')">@csrf @method('DELETE')<button class="text-red-600">Hapus</button></form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="p-6 text-center text-gray-500">Belum ada kegiatan.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $events->links() }}</div>
    </div>
</x-app-layout>
