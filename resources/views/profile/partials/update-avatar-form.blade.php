<section x-data="{
        preview: null,
        error: @js(request('upload_error') === 'too_large' && ! session('status') && ! $errors->has('avatar') ? 'Ukuran file terlalu besar. Maksimal 2 MB.' : null),
        init() {
            const url = new URL(window.location.href);
            if (url.searchParams.has('upload_error')) { url.searchParams.delete('upload_error'); history.replaceState(null, '', url.pathname + url.search + url.hash); }
        },
        check(e) {
            const f = e.target.files[0];
            this.error = null; this.preview = null;
            if (!f) return;
            if (!['image/jpeg', 'image/png', 'image/webp'].includes(f.type)) {
                this.error = 'Format foto harus JPG, PNG, atau WEBP.'; e.target.value = ''; return;
            }
            if (f.size > 2 * 1024 * 1024) {
                this.error = 'Ukuran file terlalu besar. Maksimal 2 MB.'; e.target.value = ''; return;
            }
            this.preview = URL.createObjectURL(f);
        },
        submit(e) {
            const input = e.target.querySelector('input[type=file]');
            if (this.error || !input.files.length) { e.preventDefault(); this.error ??= 'Pilih foto terlebih dahulu.'; }
        }
    }">
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
            <form method="post" action="{{ route('profile.avatar.update') }}" enctype="multipart/form-data" @submit="submit($event)" class="flex flex-wrap items-center gap-3">
                @csrf
                <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp"
                       @change="check($event)"
                       class="text-sm file:mr-3 file:rounded-md file:border-0 file:bg-gray-100 file:px-3 file:py-2 file:text-sm hover:file:bg-gray-200">
                <x-primary-button>Simpan Foto</x-primary-button>
            </form>
            @if ($user->avatar_path)
                <form method="post" action="{{ route('profile.avatar.destroy') }}" onsubmit="return confirm('Hapus foto profil?')">
                    @csrf @method('delete')
                    <button class="text-sm text-red-600 hover:underline">Hapus foto</button>
                </form>
            @endif
            <p x-show="error" x-cloak x-text="error" class="text-sm text-red-600" role="alert"></p>
            <x-input-error :messages="$errors->get('avatar')" />
            @if (session('status') === 'avatar-updated')<p class="text-sm text-green-700">Foto profil diperbarui.</p>@endif
            @if (session('status') === 'avatar-removed')<p class="text-sm text-green-700">Foto profil dihapus.</p>@endif
        </div>
    </div>
</section>
