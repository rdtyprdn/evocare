<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div class="flex items-center gap-3">
                @php $org = Auth::user()->organization; @endphp
                @if ($org)<x-org-logo :org="$org" class="w-12 h-12 text-lg" />@endif
                <div>
                    <h2 class="font-semibold text-xl">Dashboard Penyelenggara</h2>
                    @if ($org)
                        <p class="text-sm text-gray-600 flex items-center gap-2">{{ $org->name }}
                            @if ($org->isVerified())<x-verified-badge />@else<span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs text-amber-800">{{ \App\Models\Organization::STATUSES[$org->status] }}</span>@endif
                        </p>
                    @elseif (! Auth::user()->isAdmin())
                        <p class="text-sm text-gray-500">Belum ada organisasi. <a href="{{ route('organizations.create') }}" class="text-orange-600 underline">Daftar sebagai penyelenggara</a></p>
                    @endif
                </div>
            </div>
            @can('create', \App\Models\Event::class)
                <a href="{{ route('organizer.events.create') }}" class="rounded-md bg-orange-600 px-4 py-2 text-white text-sm hover:bg-orange-700">+ Buat Kegiatan</a>
            @else
                <span class="rounded-md bg-gray-200 px-4 py-2 text-gray-500 text-sm cursor-not-allowed" title="Butuh organisasi terverifikasi">+ Buat Kegiatan</span>
            @endcan
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
                        <td class="p-3">{{ $event->category->name }}@unless ($event->organization)<div class="text-xs text-amber-700">Belum terhubung organisasi</div>@endunless</td>
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
