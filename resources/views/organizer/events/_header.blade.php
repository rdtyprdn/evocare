<div class="space-y-4">
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
        <div class="flex items-center gap-3">
            @php $org = Auth::user()->organization; @endphp
            @if ($org)<x-org-logo :org="$org" class="w-12 h-12 text-lg" />@endif
            <div>
                <h2 class="font-semibold text-xl text-gray-900">{{ $title ?? 'Dashboard Penyelenggara' }}</h2>
                @if ($org)
                    <p class="text-sm text-gray-600 flex items-center gap-2">{{ $org->name }}
                        @if ($org->isVerified())<x-verified-badge />@else<span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs text-amber-800">{{ \App\Models\Organization::STATUSES[$org->status] }}</span>@endif
                    </p>
                @elseif (! Auth::user()->isAdmin())
                    <p class="text-sm text-gray-500">Belum ada organisasi. <a href="{{ route('organizations.create') }}" class="text-orange-600 underline">Daftar sebagai penyelenggara</a></p>
                @endif
            </div>
        </div>
        <div class="flex items-center gap-2">
            @can('create', \App\Models\Event::class)
                <a href="{{ route('organizer.events.create') }}" class="inline-flex items-center gap-1.5 rounded-md bg-orange-600 px-4 py-2 text-white text-sm font-medium hover:bg-orange-700">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    <span>Buat Kegiatan</span>
                </a>
            @else
                <span class="rounded-md bg-gray-200 px-4 py-2 text-gray-500 text-sm cursor-not-allowed" title="Butuh organisasi terverifikasi">+ Buat Kegiatan</span>
            @endcan
        </div>
    </div>

    <div class="flex flex-wrap gap-2 border-t border-gray-100 pt-3 text-sm font-medium print:hidden">
        <a href="{{ route('organizer.events.index') }}"
           class="inline-flex items-center gap-2 rounded-lg px-3.5 py-2 transition {{ request()->routeIs('organizer.events.index') ? 'bg-orange-50 text-orange-700 border border-orange-200' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/></svg>
            <span>Ringkasan & Statistik</span>
        </a>
        <a href="{{ route('organizer.schedule') }}"
           class="inline-flex items-center gap-2 rounded-lg px-3.5 py-2 transition {{ request()->routeIs('organizer.schedule') ? 'bg-orange-50 text-orange-700 border border-orange-200' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/></svg>
            <span>Kelola Jadwal</span>
        </a>
        <a href="{{ route('organizer.reports') }}"
           class="inline-flex items-center gap-2 rounded-lg px-3.5 py-2 transition {{ request()->routeIs('organizer.reports', 'organizer.events.report') ? 'bg-orange-50 text-orange-700 border border-orange-200' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25M9 16.5v.75m3-3v3M15 12v5.25m-4.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
            <span>Laporan Kegiatan</span>
        </a>
    </div>
</div>
