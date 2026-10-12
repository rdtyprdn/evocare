<section x-data="{ preview: null }">
    <header>
        <h2 class="text-lg font-medium text-gray-900">Foto Profil</h2>
        <p class="mt-1 text-sm text-gray-600">Unggah foto JPG, PNG, atau WEBP dengan ukuran maksimal 2 MB.</p>
    </header>

    <div class="mt-6 flex items-center gap-6">
        <div class="relative">
            <img x-show="preview" x-cloak :src="preview" alt="Pratinjau" class="w-24 h-24 rounded-full object-cover">
            <div x-show="!preview"><x-avatar :user="$user" class="w-24 h-24 text-2xl" /></div>
        </div>
        <div class="space-y-3">
            <form method="post" action="{{ route('profile.avatar.update') }}" enctype="multipart/form-data" class="flex flex-wrap items-center gap-3">
                @csrf
                <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp"
                       @change="const f = $event.target.files[0]; preview = f ? URL.createObjectURL(f) : null"
                       class="text-sm file:mr-3 file:rounded-md file:border-0 file:bg-gray-100 file:px-3 file:py-2 file:text-sm hover:file:bg-gray-200">
                <x-primary-button>Simpan Foto</x-primary-button>
            </form>
            @if ($user->avatar_path)
                <form method="post" action="{{ route('profile.avatar.destroy') }}" onsubmit="return confirm('Hapus foto profil?')">
                    @csrf @method('delete')
                    <button class="text-sm text-red-600 hover:underline">Hapus foto</button>
                </form>
            @endif
            <x-input-error :messages="$errors->get('avatar')" />
            @if (session('status') === 'avatar-updated')<p class="text-sm text-green-700">Foto profil diperbarui.</p>@endif
            @if (session('status') === 'avatar-removed')<p class="text-sm text-green-700">Foto profil dihapus.</p>@endif
        </div>
    </div>
</section>
