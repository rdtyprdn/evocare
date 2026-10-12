<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">Admin · Verifikasi Organisasi</h2></x-slot>
    <div class="max-w-5xl mx-auto px-4 py-8 space-y-4">
        <div class="flex gap-2 text-sm">
            @foreach (['pending' => 'Menunggu', 'verified' => 'Terverifikasi', 'rejected' => 'Ditolak', 'all' => 'Semua'] as $key => $label)
                <a href="{{ route('admin.organizations.index', ['status' => $key]) }}"
                   class="px-3 py-1 rounded-full border {{ $status === $key ? 'bg-orange-600 text-white border-orange-600' : 'bg-white' }}">
                    {{ $label }}@if ($key !== 'all') ({{ $counts[$key] ?? 0 }})@endif
                </a>
            @endforeach
        </div>

        @forelse ($organizations as $org)
            <div class="bg-white rounded-xl shadow-sm p-5 flex flex-col md:flex-row gap-4">
                <x-org-logo :org="$org" class="w-14 h-14 text-lg" />
                <div class="flex-1 space-y-1">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h3 class="font-semibold">{{ $org->name }}</h3>
                        @if ($org->isVerified())<x-verified-badge />@else<span class="rounded-full px-2 py-0.5 text-xs {{ $org->status === 'rejected' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-800' }}">{{ \App\Models\Organization::STATUSES[$org->status] }}</span>@endif
                    </div>
                    <p class="text-sm text-gray-500">{{ $org->typeLabel() }} · {{ $org->city }} · {{ $org->events_count }} kegiatan</p>
                    <p class="text-sm">Pemilik: {{ $org->owner?->name ?? '-' }} {{ $org->owner ? '('.$org->owner->email.')' : '' }} · Kontak: {{ $org->contact ?? '-' }}</p>
                    <p class="text-sm text-gray-600 whitespace-pre-line">{{ $org->description }}</p>
                    @if ($org->admin_note)<p class="text-sm text-red-700">Catatan admin: {{ $org->admin_note }}</p>@endif
                </div>
                <div class="md:w-64 space-y-2">
                    @unless ($org->isVerified())
                        <form method="POST" action="{{ route('admin.organizations.verify', $org) }}">@csrf
                            <button class="w-full rounded-md bg-green-600 px-3 py-2 text-sm text-white hover:bg-green-700">Verifikasi</button>
                        </form>
                    @endunless
                    @if ($org->status !== 'rejected')
                        <form method="POST" action="{{ route('admin.organizations.reject', $org) }}" class="space-y-2">@csrf
                            <textarea name="admin_note" rows="2" required placeholder="Alasan penolakan" class="w-full rounded-md border-gray-300 text-sm"></textarea>
                            <button class="w-full rounded-md border border-red-300 px-3 py-2 text-sm text-red-600 hover:bg-red-50">Tolak</button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <div class="bg-white rounded-xl p-10 text-center text-gray-500">Tidak ada organisasi pada status ini.</div>
        @endforelse
    </div>
</x-app-layout>
