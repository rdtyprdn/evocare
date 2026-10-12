<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">{{ $event->exists ? 'Ubah Kegiatan' : 'Buat Kegiatan Baru' }}</h2></x-slot>
    <div class="max-w-3xl mx-auto px-4 py-8">
        <form method="POST" action="{{ $event->exists ? route('organizer.events.update', $event) : route('organizer.events.store') }}" class="bg-white rounded-xl shadow-sm p-6 space-y-4">
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
                    <x-text-input type="url" id="image_url" name="image_url" class="mt-1 w-full" :value="old('image_url', $event->image_url)" />
                    <x-input-error :messages="$errors->get('image_url')" class="mt-1" />
                </div>
            </div>
            <div class="flex justify-end gap-3">
                <a href="{{ route('organizer.events.index') }}" class="px-4 py-2 text-gray-600">Batal</a>
                <x-primary-button>Simpan</x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>
