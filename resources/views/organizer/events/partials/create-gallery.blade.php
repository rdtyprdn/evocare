@php
    $mediaErr = fn ($k) => array_merge($errors->get($k), $errors->get($k.'.*') ? array_merge(...array_values($errors->get($k.'.*'))) : []);
    $initialTab = ($errors->has('youtube_links') || $errors->has('youtube_links.*') || $errors->has('youtube_captions') || $errors->has('youtube_captions.*')) ? 'youtube'
        : (($errors->has('videos') || $errors->has('videos.*') || $errors->has('video_captions') || $errors->has('video_captions.*')) ? 'video' : 'photo');
    $oldLinkVals = array_values((array) old('youtube_links', []));
    $oldLinkCaps = array_values((array) old('youtube_captions', []));
    $oldLinks = [];
    foreach ($oldLinkVals as $i => $v) {
        if (! is_string($v) || trim($v) === '') {
            continue;
        }
        $cap = $oldLinkCaps[$i] ?? '';
        $oldLinks[] = ['v' => $v, 'caption' => is_string($cap) ? $cap : ''];
    }
    $iconImg = 'm2.25 15.75 5.16-5.16a2.25 2.25 0 0 1 3.18 0l5.16 5.16m-1.5-1.5 1.41-1.41a2.25 2.25 0 0 1 3.18 0l2.91 2.91M3.75 19.5h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Z';
    $iconVid = 'm15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z';
    $iconX = 'M6 18 18 6M6 6l12 12';
@endphp
@php($existingCount = $event->exists ? $event->photos->count() : 0)
<section id="galeri" class="rounded-xl border border-gray-200 p-5 space-y-4 scroll-mt-4" x-data="createGallery(@js($initialTab), @js($oldLinks), {{ $existingCount }})">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h3 class="text-base font-semibold text-gray-900">Galeri Dokumentasi <span class="text-sm font-normal text-gray-500">(opsional)</span></h3>
            <p class="text-sm text-gray-500">Tambahkan foto, video, atau tautan YouTube. Semuanya tampil di halaman detail kegiatan.</p>
            <p class="text-xs text-gray-500 mt-1">Item baru diunggah saat Anda menekan Simpan. Total unggahan sekali simpan maksimal 120 MB. Galeri maksimal 30 item.</p>
        </div>
        <span class="text-sm text-gray-500 shrink-0"><span x-text="itemCount() + existing">{{ $existingCount }}</span> / 30 item</span>
    </div>

    <div class="inline-flex rounded-lg border border-gray-200 bg-gray-50 p-1 text-sm" role="tablist">
        @foreach (['photo' => 'Foto', 'video' => 'Video', 'youtube' => 'Link YouTube'] as $key => $label)
            <button type="button" role="tab" @click="tab = '{{ $key }}'" :aria-selected="tab === '{{ $key }}'"
                    :class="tab === '{{ $key }}' ? 'bg-white text-orange-700 shadow-sm' : 'text-gray-600 hover:text-gray-900'"
                    class="rounded-md px-3 py-1.5 font-medium transition">{{ $label }}</button>
        @endforeach
    </div>

    @if ($errors->any() && old('title') !== null)
        <p class="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-md px-3 py-2">Demi keamanan, browser tidak menyimpan file yang sudah dipilih. Silakan pilih ulang foto atau video sebelum menyimpan.</p>
    @endif

    @foreach ([
        'photo' => ['field' => 'photos', 'accept' => 'image/jpeg,image/png,image/webp', 'icon' => $iconImg, 'label' => 'Pilih foto dokumentasi', 'help' => 'Maks 10 MB per foto, JPG PNG WEBP. Hingga 10 foto.', 'captions' => 'photo_captions'],
        'video' => ['field' => 'videos', 'accept' => 'video/mp4,video/webm,video/quicktime', 'icon' => $iconVid, 'label' => 'Pilih video dokumentasi', 'help' => 'Maks 30 MB per video, MP4 atau WEBM (MOV juga bisa). Hingga 3 video.', 'captions' => 'video_captions'],
    ] as $kind => $cfg)
        <div x-show="tab === '{{ $kind }}'" @if ($kind !== $initialTab) x-cloak @endif class="space-y-3">
            <label class="flex flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed border-gray-300 p-6 text-center cursor-pointer hover:border-orange-400 hover:bg-orange-50/40 transition">
                <svg class="w-8 h-8 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $cfg['icon'] }}"/></svg>
                <span class="text-sm font-medium text-gray-700">{{ $cfg['label'] }}</span>
                <span class="text-xs text-gray-500">{{ $cfg['help'] }}</span>
                <input type="file" multiple accept="{{ $cfg['accept'] }}" class="sr-only" @change="pick('{{ $cfg['field'] }}', $event); $event.target.value = ''">
            </label>
            <input type="file" name="{{ $cfg['field'] }}[]" multiple x-ref="{{ $cfg['field'] }}" class="hidden" tabindex="-1" aria-hidden="true">

            <ul x-show="{{ $cfg['field'] }}.length" x-cloak class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <template x-for="(item, i) in {{ $cfg['field'] }}" :key="item.url">
                    <li class="rounded-lg border border-gray-200 overflow-hidden bg-white">
                        <div class="relative aspect-video bg-gray-900">
                            @if ($kind === 'photo')
                                <img :src="item.url" :alt="item.file.name" class="w-full h-full object-cover">
                            @else
                                <video :src="item.url" muted preload="metadata" class="w-full h-full object-cover"></video>
                            @endif
                            <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/70 to-transparent px-2 py-1 text-[11px] text-white truncate" x-text="item.file.name + ' · ' + fmt(item.file.size)"></div>
                            <button type="button" @click="removeFile('{{ $cfg['field'] }}', i)" class="absolute top-1.5 right-1.5 rounded-full bg-white/90 p-1 text-gray-700 hover:text-red-600" aria-label="Hapus dari pilihan">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconX }}"/></svg>
                            </button>
                        </div>
                        <div class="p-2">
                            <input type="text" :name="'{{ $cfg['captions'] }}[' + i + ']'" x-model="item.caption" maxlength="150"
                                   placeholder="{{ $kind === 'photo' ? 'Keterangan foto (opsional)' : 'Keterangan video (opsional)' }}"
                                   class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-orange-500 focus:ring-orange-500">
                        </div>
                    </li>
                </template>
            </ul>
            <x-input-error :messages="array_merge($mediaErr($cfg['field']), $mediaErr($cfg['captions']))" />
        </div>
    @endforeach

    <div x-show="tab === 'youtube'" @if ($initialTab !== 'youtube') x-cloak @endif class="space-y-3">
        <template x-for="(link, i) in links" :key="i">
            <div class="rounded-lg border border-gray-200 p-3 space-y-2 bg-white">
                <div class="flex gap-2 items-center">
                    <input type="url" name="youtube_links[]" x-model="link.v" placeholder="https://www.youtube.com/watch?v=..." class="flex-1 rounded-md border-gray-300 shadow-sm text-sm focus:border-orange-500 focus:ring-orange-500">
                    <button type="button" @click="removeLink(i)" class="p-2 text-gray-500 hover:text-red-600" aria-label="Hapus tautan">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconX }}"/></svg>
                    </button>
                </div>
                <input type="text" name="youtube_captions[]" x-model="link.caption" maxlength="150" placeholder="Keterangan tautan (opsional)"
                       class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-orange-500 focus:ring-orange-500">
            </div>
        </template>
        <button type="button" @click="addLink()" class="inline-flex items-center gap-1.5 text-sm font-medium text-orange-600 hover:text-orange-700">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
            Tambah tautan
        </button>
        <p class="text-xs text-gray-500">Mendukung youtube.com/watch?v=..., youtu.be/..., dan youtube.com/shorts/....</p>
        <x-input-error :messages="array_merge($mediaErr('youtube_links'), $mediaErr('youtube_captions'))" />
    </div>

    @if ($event->exists)
        <div class="space-y-2 border-t border-gray-100 pt-4">
            <h4 class="text-sm font-semibold text-gray-700">Tersimpan di galeri</h4>
                    @if ($event->photos->isNotEmpty())
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                            @foreach ($event->photos as $photo)
                                <div class="rounded-xl border border-gray-200 overflow-hidden bg-gray-50">
                                    <div class="relative h-32 bg-gray-200">
                                        @if ($photo->isVideo())
                                            <video src="{{ $photo->url() }}#t=0.5" preload="metadata" muted playsinline class="w-full h-full object-cover"></video>
                                        @else
                                            <img src="{{ $photo->thumbnailUrl() }}" alt="{{ $photo->caption ?? 'Dokumentasi' }}" class="w-full h-full object-cover" loading="lazy">
                                        @endif
                                        @unless ($photo->isImage())
                                            <x-gallery-play-icon class="w-10 h-10" />
                                        @endunless
                                        <span class="absolute top-2 left-2 rounded-full bg-black/60 px-2 py-0.5 text-[11px] font-medium text-white">{{ $photo->typeLabel() }}</span>
                                    </div>
                                    <div class="p-2 space-y-2">
                                        <input type="text" name="existing_captions[{{ $photo->id }}]" value="{{ old('existing_captions.'.$photo->id, $photo->caption) }}" maxlength="150"
                                               placeholder="Keterangan (opsional)"
                                               class="w-full rounded-md border-gray-300 shadow-sm text-xs focus:border-orange-500 focus:ring-orange-500">
                                        <div class="flex justify-end">
                                            <x-confirm-delete-modal :action="route('organizer.events.photos.destroy', [$event, $photo])" :title="'Hapus '.$photo->typeLabel().' Dokumentasi?'" description="Item ini akan dihapus dari galeri kegiatan dan tidak dapat dikembalikan." :item-name="$photo->caption ?? $photo->typeLabel().' dokumentasi'" trigger-label="Hapus" trigger-class="text-xs text-red-600 hover:underline shrink-0" confirm-label="Ya, Hapus" />
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-500 text-center py-4">Belum ada foto atau video dokumentasi tersimpan.</p>
                    @endif
        </div>
    @endif

    <p x-show="error" x-cloak x-text="error" class="text-sm text-red-600" role="alert"></p>
    <x-input-error :messages="$errors->get('gallery')" />
</section>
