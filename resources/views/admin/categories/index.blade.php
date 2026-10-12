<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">Admin · Kategori Isu</h2></x-slot>
    <div class="max-w-3xl mx-auto px-4 py-8 space-y-6">
        <form method="POST" action="{{ route('admin.categories.store') }}" class="bg-white rounded-xl shadow-sm p-4 flex gap-3 items-start">
            @csrf
            <div class="flex-1"><x-text-input name="name" placeholder="Nama kategori baru" class="w-full" required /><x-input-error :messages="$errors->get('name')" class="mt-1" /></div>
            <x-text-input name="icon" placeholder="Ikon (emoji)" class="w-32" />
            <x-primary-button>Tambah</x-primary-button>
        </form>
        <div class="bg-white rounded-xl shadow-sm divide-y">
            @foreach ($categories as $c)
                <div class="p-4 flex justify-between items-center">
                    <span>{{ $c->icon }} {{ $c->name }} <span class="text-gray-400 text-sm">({{ $c->events_count }} kegiatan)</span></span>
                    <form method="POST" action="{{ route('admin.categories.destroy', $c) }}">@csrf @method('DELETE')<button class="text-red-600 text-sm">Hapus</button></form>
                </div>
            @endforeach
        </div>
    </div>
</x-app-layout>
