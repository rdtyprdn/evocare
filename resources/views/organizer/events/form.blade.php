<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">{{ $event->exists ? 'Ubah Kegiatan' : 'Buat Kegiatan Baru' }}</h2></x-slot>
    <div class="max-w-3xl mx-auto px-4 py-8">
        <form method="POST" action="{{ $event->exists ? route('organizer.events.update', $event) : route('organizer.events.store') }}" enctype="multipart/form-data" class="bg-white rounded-xl shadow-sm p-6 space-y-4"
              x-data="eventImageForm(@js($event->image_path ? asset('storage/'.$event->image_path) : null), @js(request('upload_error') === 'too_large' ? 'Ukuran file terlalu besar. Maksimal 2 MB.' : null))" @submit="submit($event)">
            @csrf
            @if ($event->exists) @method('PUT') @endif
            <div>
                <x-input-label for="title" value="Judul" />
                <x-text-input id="title" name="title" class="mt-1 w-full" :value="old('title', $event->title)" required />
                <x-input-error :messages="$errors->get('title')" class="mt-1" />
            </div>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="category_id" value="Kategori" />
                    <select id="category_id" name="category_id" class="mt-1 w-full rounded-md border-gray-300">
                        @foreach ($categories as $c)
                            <option value="{{ $c->id }}" @selected(old('category_id', $event->category_id) == $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('category_id')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="status" value="Status" />
                    <select id="status" name="status" class="mt-1 w-full rounded-md border-gray-300">
                        @foreach (\App\Models\Event::STATUSES as $val => $label)
                            <option value="{{ $val }}" @selected(old('status', $event->status) === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div>
                <x-input-label for="description" value="Deskripsi" />
                <textarea id="description" name="description" rows="5" class="mt-1 w-full rounded-md border-gray-300" required>{{ old('description', $event->description) }}</textarea>
                <x-input-error :messages="$errors->get('description')" class="mt-1" />
            </div>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="city" value="Kota" />
                    <x-text-input id="city" name="city" class="mt-1 w-full" :value="old('city', $event->city)" required />
                    <x-input-error :messages="$errors->get('city')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="location" value="Lokasi / Alamat" />
                    <x-text-input id="location" name="location" class="mt-1 w-full" :value="old('location', $event->location)" required />
                    <x-input-error :messages="$errors->get('location')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="start_at" value="Waktu Mulai" />
                    <x-text-input type="datetime-local" id="start_at" name="start_at" class="mt-1 w-full" :value="old('start_at', $event->start_at?->format('Y-m-d\TH:i'))" required />
                    <x-input-error :messages="$errors->get('start_at')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="end_at" value="Waktu Selesai" />
                    <x-text-input type="datetime-local" id="end_at" name="end_at" class="mt-1 w-full" :value="old('end_at', $event->end_at?->format('Y-m-d\TH:i'))" required />
                    <x-input-error :messages="$errors->get('end_at')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="quota" value="Kuota" />
                    <x-text-input type="number" min="1" id="quota" name="quota" class="mt-1 w-full" :value="old('quota', $event->quota)" required />
                    <x-input-error :messages="$errors->get('quota')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="image_url" value="URL Gambar (opsional)" />
                    <x-text-input type="url" id="image_url" name="image_url" class="mt-1 w-full" :value="old('image_url', $event->image_url)" x-model="url" />
                    <x-input-error :messages="$errors->get('image_url')" class="mt-1" />
                </div>
            </div>
            <div>
                <x-input-label for="image" value="Gambar Kegiatan (opsional)" />
                <p class="text-xs text-gray-500">JPG, PNG, atau WEBP, maksimal 2 MB. Jika diunggah, gambar ini dipakai menggantikan URL gambar.</p>
                <div class="mt-2 flex flex-col sm:flex-row gap-4 items-start">
                    <div class="w-48 h-28 rounded-lg overflow-hidden bg-gray-100 flex items-center justify-center text-gray-300 shrink-0">
                        <img x-show="shown()" x-cloak :src="shown()" alt="Pratinjau gambar kegiatan" class="w-full h-full object-cover">
                        <svg x-show="!shown()" class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Z"/></svg>
                    </div>
                    <div class="space-y-2">
                        <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp" @change="check($event)"
                               class="text-sm file:mr-3 file:rounded-md file:border-0 file:bg-gray-100 file:px-3 file:py-2 file:text-sm hover:file:bg-gray-200">
                        @if ($event->image_path)
                            <label class="flex items-center gap-2 text-sm text-red-600">
                                <input type="checkbox" name="remove_image" value="1" x-model="remove" class="rounded border-gray-300"> Hapus gambar yang diunggah
                            </label>
                        @endif
                        <p x-show="error" x-cloak x-text="error" class="text-sm text-red-600" role="alert"></p>
                        <x-input-error :messages="$errors->get('image')" />
                    </div>
                </div>
            </div>
            <div class="flex justify-end gap-3">
                <a href="{{ route('organizer.events.index') }}" class="px-4 py-2 text-gray-600">Batal</a>
                <x-primary-button>Simpan</x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>
