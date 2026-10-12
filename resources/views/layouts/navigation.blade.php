<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center gap-8">
                <a href="{{ route('home') }}" class="text-2xl font-bold text-orange-600">Evo<span class="text-gray-800">Care</span></a>
                <div class="hidden sm:flex gap-6 text-sm font-medium">
                    <a href="{{ route('home') }}" class="hover:text-orange-600 {{ request()->routeIs('home') ? 'text-orange-600' : '' }}">Jelajahi Kegiatan</a>
                    @auth
                        <a href="{{ route('registrations.index') }}" class="hover:text-orange-600 {{ request()->routeIs('registrations.*') ? 'text-orange-600' : '' }}">Pendaftaran Saya</a>
                        @if (Auth::user()->canManageEvents())
                            <a href="{{ route('organizer.events.index') }}" class="hover:text-orange-600 {{ request()->routeIs('organizer.*') ? 'text-orange-600' : '' }}">Dashboard Penyelenggara</a>
                        @endif
                        @if (! Auth::user()->organization_id && ! Auth::user()->isAdmin())
                            <a href="{{ route('organizations.create') }}" class="hover:text-orange-600 {{ request()->routeIs('organizations.*') ? 'text-orange-600' : '' }}">Daftar sebagai Penyelenggara</a>
                        @endif
                        @if (Auth::user()->isAdmin())
                            <a href="{{ route('admin.organizations.index') }}" class="hover:text-orange-600 {{ request()->routeIs('admin.organizations.*') ? 'text-orange-600' : '' }}">Verifikasi Organisasi</a>
                            <a href="{{ route('admin.categories.index') }}" class="hover:text-orange-600 {{ request()->routeIs('admin.categories.*') ? 'text-orange-600' : '' }}">Kategori</a>
                        @endif
                    @endauth
                </div>
            </div>
            <div class="hidden sm:flex items-center gap-4 text-sm">
                @auth
                    <a href="{{ route('profile.edit') }}" class="flex items-center gap-2 text-gray-600 hover:text-gray-900"><x-avatar :user="Auth::user()" class="w-8 h-8 text-xs" /><span class="flex flex-col leading-tight"><span>{{ Auth::user()->name }}</span>@if (Auth::user()->organization)<span class="text-xs text-gray-400">{{ Auth::user()->organization->name }}</span>@endif</span> <span class="text-xs text-gray-400">({{ \App\Models\User::ROLE_LABELS[Auth::user()->role] ?? Auth::user()->role }})</span></a>
                    <form method="POST" action="{{ route('logout') }}">@csrf<button class="text-gray-600 hover:text-red-600">Keluar</button></form>
                @else
                    <a href="{{ route('login') }}" class="text-gray-600 hover:text-gray-900">Masuk</a>
                    <a href="{{ route('register') }}" class="rounded-md bg-orange-600 px-4 py-2 text-white hover:bg-orange-700">Daftar</a>
                @endauth
            </div>
            <div class="flex items-center sm:hidden">
                <button @click="open = ! open" class="p-2 text-gray-500"><svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg></button>
            </div>
        </div>
    </div>
    <div x-show="open" class="sm:hidden border-t px-4 py-3 space-y-2 text-sm">
        <a class="block" href="{{ route('home') }}">Jelajahi Kegiatan</a>
        @auth
            <div class="flex items-center gap-3 pb-2 border-b"><x-avatar :user="Auth::user()" class="w-10 h-10 text-sm" /><div><div class="font-medium">{{ Auth::user()->name }}</div><div class="text-xs text-gray-500">{{ Auth::user()->email }}</div></div></div>
            <a class="block" href="{{ route('registrations.index') }}">Pendaftaran Saya</a>
            @if (Auth::user()->canManageEvents())<a class="block" href="{{ route('organizer.events.index') }}">Dashboard Penyelenggara</a>@endif
            @if (! Auth::user()->organization_id && ! Auth::user()->isAdmin())<a class="block" href="{{ route('organizations.create') }}">Daftar sebagai Penyelenggara</a>@endif
            @if (Auth::user()->organization)<a class="block text-gray-500" href="{{ route('organizations.create') }}">Organisasi: {{ Auth::user()->organization->name }}</a>@endif
            @if (Auth::user()->isAdmin())<a class="block" href="{{ route('admin.organizations.index') }}">Verifikasi Organisasi</a><a class="block" href="{{ route('admin.categories.index') }}">Kategori</a>@endif
            <a class="block" href="{{ route('profile.edit') }}">Profil</a>
            <form method="POST" action="{{ route('logout') }}">@csrf<button>Keluar</button></form>
        @else
            <a class="block" href="{{ route('login') }}">Masuk</a>
            <a class="block" href="{{ route('register') }}">Daftar</a>
        @endauth
    </div>
</nav>
