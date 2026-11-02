<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">Admin · Jenis Organisasi</h2></x-slot>
    <div class="max-w-3xl mx-auto px-4 py-8 space-y-6">
        <p class="text-sm text-gray-600">Kelola pilihan jenis organisasi yang tampil saat pengguna mendaftar sebagai penyelenggara.</p>
        <form method="POST" action="{{ route('admin.organization-types.store') }}" class="bg-white rounded-xl shadow-sm p-4 flex gap-3 items-start">
            @csrf
            <div class="flex-1"><x-text-input name="name" :value="old('name')" placeholder="Nama jenis organisasi baru" class="w-full" required maxlength="80" /><x-input-error :messages="$errors->get('name')" class="mt-1" /></div>
            <x-primary-button class="gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
                Tambah
            </x-primary-button>
        </form>
        <div class="bg-white rounded-xl shadow-sm divide-y">
            @forelse ($types as $t)
                @php($bag = 'rename'.$t->id)
                <div class="p-4" x-data="{ blocked: false, editing: {{ $errors->$bag->any() ? 'true' : 'false' }} }">
                    <div x-show="!editing" class="flex justify-between items-center gap-3">
                        <span>{{ $t->name }} <span class="text-gray-400 text-sm">({{ $t->organizations_count }} organisasi)</span></span>
                        <div class="flex items-center gap-4 text-sm">
                            <button type="button" @click="editing = true" class="inline-flex items-center gap-1 text-orange-600 hover:underline">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.86 4.49l2.65 2.65M4 20l1-4L16.5 4.5a1.5 1.5 0 012.12 0l.88.88a1.5 1.5 0 010 2.12L8 19l-4 1z"/></svg>
                                Ubah
                            </button>
                            @if ($t->organizations_count > 0)
                                <button type="button" aria-disabled="true" @click="blocked = true" class="text-gray-400 cursor-not-allowed" title="Masih dipakai oleh {{ $t->organizations_count }} organisasi">Hapus</button>
                            @else
                                <x-confirm-delete-modal :action="route('admin.organization-types.destroy', $t)" title="Hapus Jenis Organisasi?" description="Jenis organisasi ini akan dihapus dari pilihan pendaftaran penyelenggara." :item-name="$t->name" trigger-label="Hapus" confirm-label="Ya, Hapus" />
                            @endif
                        </div>
                    </div>
                    <p x-show="blocked && !editing" x-cloak class="mt-2 text-sm text-red-600">Tidak dapat dihapus karena masih dipakai oleh {{ $t->organizations_count }} organisasi. Pindahkan dulu organisasi tersebut ke pilihan lain.</p>
                    <form x-show="editing" x-cloak method="POST" action="{{ route('admin.organization-types.update', $t) }}" class="flex gap-3 items-start">
                        @csrf @method('PUT')
                        <div class="flex-1"><x-text-input name="name" :value="$errors->$bag->any() ? old('name') : $t->name" class="w-full" required maxlength="80" /><x-input-error :messages="$errors->$bag->get('name')" class="mt-1" /></div>
                        <x-primary-button>Simpan</x-primary-button>
                        <button type="button" @click="editing = false" class="text-sm text-gray-600 py-2">Batal</button>
                    </form>
                </div>
            @empty
                <p class="p-4 text-sm text-gray-500">Belum ada jenis organisasi.</p>
            @endforelse
        </div>
    </div>
</x-app-layout>
