@props(['event'])

@php
    $photos = $event->photos;
    $items = $photos->map(fn ($p) => ['type' => $p->type, 'src' => $p->url(), 'caption' => $p->caption])->values();
    $canManage = auth()->check() && auth()->user()->can('managePhotos', $event);
@endphp

<section class="pt-6 space-y-3" aria-labelledby="galeri-dokumentasi"
         x-data="{
            photos: @js($items),
            open: false,
            index: 0,
            durations: {},
            stopMedia() { document.querySelectorAll('[data-gallery-player]').forEach(v => v.pause()); },
            touchX: null,
            swipeEnd(e) { if (this.touchX === null) return; const dx = e.changedTouches[0].clientX - this.touchX; this.touchX = null; if (Math.abs(dx) > 50) { dx < 0 ? this.next() : this.prev(); } },
            show(i) { this.index = i; this.open = true; document.body.classList.add('overflow-hidden'); },
            close() { this.stopMedia(); this.open = false; document.body.classList.remove('overflow-hidden'); },
            next() { this.stopMedia(); this.index = (this.index + 1) % this.photos.length; },
            prev() { this.stopMedia(); this.index = (this.index - 1 + this.photos.length) % this.photos.length; },
            fmt(s) { if (! s || ! isFinite(s)) return ''; s = Math.round(s); return Math.floor(s / 60) + ':' + String(s % 60).padStart(2, '0'); },
            current() { return this.photos[this.index] || {}; },
            scroll(dir) { this.$refs.track.scrollBy({ left: dir * this.$refs.track.clientWidth * 0.8, behavior: 'smooth' }); },
         }"
         @keydown.window.escape="open && close()"
         @keydown.window.arrow-right="open && next()"
         @keydown.window.arrow-left="open && prev()">
    <div class="flex items-center justify-between gap-3">
        <h2 id="galeri-dokumentasi" class="text-xl font-bold text-gray-900">Galeri Dokumentasi</h2>
        <div class="flex items-center gap-2">
            @if ($canManage)
                <a href="{{ route('organizer.events.edit', $event) }}#galeri" class="text-sm font-medium text-orange-600 hover:underline">Kelola galeri</a>
            @endif
            @if ($photos->count() > 1)
                <button type="button" @click="scroll(-1)" aria-label="Geser ke kiri" class="w-9 h-9 rounded-full border border-gray-200 bg-white text-gray-600 hover:text-orange-600 hover:border-orange-300 flex items-center justify-center transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
                </button>
                <button type="button" @click="scroll(1)" aria-label="Geser ke kanan" class="w-9 h-9 rounded-full border border-gray-200 bg-white text-gray-600 hover:text-orange-600 hover:border-orange-300 flex items-center justify-center transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                </button>
            @endif
        </div>
    </div>

    @if ($photos->isEmpty())
        <div class="rounded-xl border border-dashed border-gray-300 bg-white p-6 text-center">
            <svg class="w-8 h-8 mx-auto text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.16-5.16a2.25 2.25 0 0 1 3.18 0l5.16 5.16m-1.5-1.5 1.41-1.41a2.25 2.25 0 0 1 3.18 0l2.91 2.91M3.75 19.5h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Z"/></svg>
            <p class="mt-2 text-sm text-gray-500">Belum ada foto atau video dokumentasi untuk kegiatan ini.</p>
            @if ($canManage)
                <a href="{{ route('organizer.events.edit', $event) }}#galeri" class="mt-3 inline-flex items-center gap-1.5 rounded-md bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700 transition">Tambah foto atau video</a>
            @endif
        </div>
    @else
        <div x-ref="track" class="flex gap-3 overflow-x-auto snap-x snap-mandatory scroll-smooth pb-2">
            @foreach ($photos as $i => $photo)
                <button type="button" @click="show({{ $i }})" class="group relative shrink-0 snap-start w-56 sm:w-64 h-40 rounded-xl overflow-hidden bg-gray-100 focus:outline-none focus:ring-2 focus:ring-orange-500" aria-label="Lihat {{ strtolower($photo->typeLabel()) }} {{ $i + 1 }}">
                    @if ($photo->isVideo())
                        <video src="{{ $photo->url() }}#t=0.5" preload="metadata" muted playsinline class="w-full h-full object-cover" @loadedmetadata="durations[{{ $i }}] = fmt($event.target.duration)"></video>
                    @else
                        <img src="{{ $photo->thumbnailUrl() }}" alt="{{ $photo->caption ?? 'Dokumentasi '.($i + 1) }}" loading="lazy" class="w-full h-full object-cover transition duration-300 group-hover:scale-105">
                    @endif
                    @unless ($photo->isImage())
                        <x-gallery-play-icon class="w-12 h-12 transition group-hover:scale-110" />
                        <span class="absolute top-2 right-2 rounded-full bg-black/65 px-2 py-0.5 text-[11px] font-medium text-white">
                            @if ($photo->isYoutube()) YouTube @else <span x-text="durations[{{ $i }}] || 'Video'">Video</span> @endif
                        </span>
                    @endunless
                    @if ($photo->caption)
                        <span class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/70 to-transparent p-2 text-left text-xs text-white truncate">{{ $photo->caption }}</span>
                    @endif
                </button>
            @endforeach
        </div>

        <template x-teleport="body">
            <div x-show="open" x-cloak x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/90 p-4" role="dialog" aria-modal="true" @click.self="close()">
                <button type="button" @click="close()" aria-label="Tutup" class="absolute top-4 right-4 w-10 h-10 rounded-full bg-white/10 text-white hover:bg-white/20 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                </button>
                <button type="button" x-show="photos.length > 1" @click="prev()" aria-label="Sebelumnya" class="absolute left-3 sm:left-6 w-11 h-11 rounded-full bg-white/10 text-white hover:bg-white/20 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
                </button>
                <figure class="max-w-5xl w-full text-center" @touchstart.passive="touchX = $event.touches[0].clientX" @touchend="swipeEnd($event)">
                    <template x-if="open && current().type === 'image'">
                        <img :src="current().src" :alt="current().caption || 'Foto dokumentasi'" class="mx-auto max-h-[80vh] rounded-lg object-contain">
                    </template>
                    <template x-if="open && current().type === 'video'">
                        <video data-gallery-player :src="current().src" controls autoplay playsinline class="mx-auto max-h-[80vh] max-w-full rounded-lg bg-black"></video>
                    </template>
                    <template x-if="open && current().type === 'youtube'">
                        <div class="mx-auto w-full max-w-4xl aspect-video">
                            <iframe :src="current().src" title="Video YouTube" class="w-full h-full rounded-lg" allow="autoplay; encrypted-media; picture-in-picture; fullscreen" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe>
                        </div>
                    </template>
                    <figcaption class="mt-3 text-sm text-gray-200">
                        <span x-text="photos[index]?.caption"></span>
                        <span class="block text-xs text-gray-400 mt-1" x-text="(index + 1) + ' dari ' + photos.length"></span>
                    </figcaption>
                </figure>
                <button type="button" x-show="photos.length > 1" @click="next()" aria-label="Berikutnya" class="absolute right-3 sm:right-6 w-11 h-11 rounded-full bg-white/10 text-white hover:bg-white/20 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                </button>
            </div>
        </template>
    @endif
</section>
