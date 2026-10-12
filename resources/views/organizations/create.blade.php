<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">Daftar sebagai Penyelenggara</h2></x-slot>
    <div class="max-w-3xl mx-auto px-4 py-8 space-y-6">
        @if ($linkedOrg)
            <div class="bg-white rounded-xl shadow-sm p-6 flex items-start gap-4">
                <x-org-logo :org="$linkedOrg" class="w-16 h-16 text-xl" />
                <div class="flex-1">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h3 class="text-lg font-semibold">{{ $linkedOrg->name }}</h3>
                        @if ($linkedOrg->isVerified())
                            <x-verified-badge />
                        @else
                            <span class="rounded-full px-2 py-0.5 text-xs {{ $linkedOrg->status === 'rejected' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-800' }}">{{ \App\Models\Organization::STATUSES[$linkedOrg->status] }}</span>
                        @endif
                    </div>
                    <p class="text-sm text-gray-500">{{ $linkedOrg->typeLabel() }} · {{ $linkedOrg->city }}</p>
                    @if ($linkedOrg->status === 'rejected' && $linkedOrg->admin_note)
                        <p class="mt-2 text-sm text-red-700">Alasan penolakan: {{ $linkedOrg->admin_note }}</p>
                    @elseif ($linkedOrg->status === 'pending')
                        <p class="mt-2 text-sm text-gray-600">Data sedang ditinjau admin. Setelah terverifikasi, Anda dapat membuat dan mempublikasikan kegiatan.</p>
                    @else
                        <p class="mt-2 text-sm text-gray-600"><a class="text-orange-600 underline" href="{{ route('organizer.events.index') }}">Buka Dashboard Penyelenggara</a></p>
                    @endif
                </div>
            </div>
        @else
            <p class="text-gray-600">Daftarkan organisasi Anda (LSM, komunitas, atau institusi pendidikan) untuk membuat kegiatan. Admin akan memverifikasi data sebelum Anda bisa mempublikasikan kegiatan.</p>
        @endif

        @if ($canEdit)
            <form method="POST" action="{{ route('organizations.store') }}" enctype="multipart/form-data"
                  class="bg-white rounded-xl shadow-sm p-6 space-y-4"
                  x-data="eventImageForm(@js($organization->logoUrl()), @js(request('upload_error') === 'too_large' ? 'Ukuran file terlalu besar. Maksimal 2 MB.' : null))" @submit="submit($event)">
                @csrf
                <h3 class="font-semibold">{{ $organization->exists ? 'Perbaiki dan Ajukan Ulang' : 'Data Organisasi' }}</h3>
                <div>
                    <x-input-label for="name" value="Nama Organisasi" />
                    <x-text-input id="name" name="name" class="mt-1 w-full" :value="old('name', $organization->name)" required />
                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                </div>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="type" value="Jenis" />
                        <select id="type" name="type" class="mt-1 w-full rounded-md border-gray-300">
                            @foreach (\App\Models\Organization::TYPES as $val => $label)
                                <option value="{{ $val }}" @selected(old('type', $organization->type) === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('type')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="city" value="Kota" />
                        <x-text-input id="city" name="city" class="mt-1 w-full" :value="old('city', $organization->city)" required />
                        <x-input-error :messages="$errors->get('city')" class="mt-1" />
                    </div>
                </div>
                <div>
                    <x-input-label for="description" value="Deskripsi" />
                    <textarea id="description" name="description" rows="4" class="mt-1 w-full rounded-md border-gray-300" required>{{ old('description', $organization->description) }}</textarea>
                    <x-input-error :messages="$errors->get('description')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="contact" value="Kontak (email / nomor WhatsApp)" />
                    <x-text-input id="contact" name="contact" class="mt-1 w-full" :value="old('contact', $organization->contact)" required />
                    <x-input-error :messages="$errors->get('contact')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="logo" value="Logo (opsional, JPG/PNG/WEBP, maks. 2 MB)" />
                    <div class="mt-2 flex items-center gap-4">
                        <div class="w-20 h-20 rounded-full overflow-hidden bg-gray-100 flex items-center justify-center text-gray-300 shrink-0">
                            <img x-show="shown()" x-cloak :src="shown()" alt="Pratinjau logo" class="w-full h-full object-cover">
                            <svg x-show="!shown()" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21"/></svg>
                        </div>
                        <div class="space-y-1">
                            <input type="file" id="logo" name="logo" accept="image/jpeg,image/png,image/webp" @change="check($event)"
                                   class="text-sm file:mr-3 file:rounded-md file:border-0 file:bg-gray-100 file:px-3 file:py-2 file:text-sm hover:file:bg-gray-200">
                            <p x-show="error" x-cloak x-text="error" class="text-sm text-red-600" role="alert"></p>
                            <x-input-error :messages="$errors->get('logo')" />
                        </div>
                    </div>
                </div>
                <div class="flex justify-end">
                    <x-primary-button>Kirim untuk Verifikasi</x-primary-button>
                </div>
            </form>
        @endif
    </div>
</x-app-layout>
