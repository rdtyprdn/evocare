@props(['event', 'canManage' => false])

@php
    $media = $event->photos;
    $items = $media->map(fn ($p) => ['type' => $p->type, 'src' => $p->url(), 'thumb' => $p->thumbnailUrl(), 'caption' => $p->caption])->values();
    $chev = ['L' => 'M15.75 19.5 8.25 12l7.5-7.5', 'R' => 'm8.25 4.5 7.5 7.5-7.5 7.5'];
@endphp

<section aria-labelledby="galeri-dokumentasi" class="space-y-3"
         x-data="{
            items: @js($items),
            index: 0,
            open: false,
            durations: {},
            touchX: null,
            current() { return this.items[this.index] || {}; },
            stopMedia() { document.querySelectorAll('[data-gallery-player]').forEach(v => v.pause()); },
            select(i) { if (i === this.index) return; this.stopMedia(); this.index = i; this.$nextTick(() => this.$refs['thumb' + i]?.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'nearest' })); },
            next() { if (this.items.length) this.select((this.index + 1) % this.items.length); },
            prev() { if (this.items.length) this.select((this.index - 1 + this.items.length) % this.items.length); },
            openLightbox() { this.stopMedia(); this.open = true; document.body.classList.add('overflow-hidden'); },
            close() { this.stopMedia(); this.open = false; document.body.classList.remove('overflow-hidden'); },
            swipeEnd(e) { if (this.touchX === null) return; const dx = e.changedTouches[0].clientX - this.touchX; this.touchX = null; if (Math.abs(dx) > 50) { dx < 0 ? this.next() : this.prev(); } },
            fmt(s) { if (! s || ! isFinite(s)) return ''; s = Math.round(s); return Math.floor(s / 60) + ':' + String(s % 60).padStart(2, '0'); },
            scroll(dir) { this.$refs.track.scrollBy({ left: dir * this.$refs.track.clientWidth * 0.8, behavior: 'smooth' }); },
         }"
         @keydown.window.escape="open && close()"
         @keydown.window.arrow-right="if (! ['INPUT','TEXTAREA','SELECT'].includes(document.activeElement?.tagName)) next()"
         @keydown.window.arrow-left="if (! ['INPUT','TEXTAREA','SELECT'].includes(document.activeElement?.tagName)) prev()">
    <h2 id="galeri-dokumentasi" class="sr-only">Galeri Dokumentasi</h2>

    @if ($media->isEmpty())
        <div class="relative aspect-video overflow-hidden rounded-xl bg-orange-100 isolate">
            @if ($event->imageSrc())
                <img src="{{ $event->imageSrc() }}" alt="{{ $event->title }}" class="w-full h-full object-cover object-center">
            @else
                <x-event-image :event="$event" class="h-full" />
            @endif
            <div class="absolute inset-x-4 bottom-4 sm:inset-x-auto sm:left-1/2 sm:-translate-x-1/2 sm:w-max max-w-full rounded-xl bg-white/90 backdrop-blur px-5 py-3 text-center shadow-sm">
                <p class="text-xs font-semibold text-orange-700">Sampul kegiatan</p>
                <p class="text-sm font-medium text-gray-700">Belum ada foto atau video dokumentasi untuk kegiatan ini.</p>
                @if ($canManage)
                    <a href="{{ route('organizer.events.edit', $event) }}#galeri" class="mt-2 inline-flex items-center gap-1.5 rounded-md bg-orange-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-orange-700 transition">Tambah foto atau video</a>
                @else
                    <p class="text-xs text-gray-500">Dokumentasi akan muncul di sini setelah penyelenggara mengunggahnya.</p>
                @endif
            </div>
        </div>
    @else
        {{-- Penampil utama --}}
        <div class="relative aspect-video overflow-hidden rounded-xl bg-black isolate"
             @touchstart.passive="touchX = $event.touches[0].clientX" @touchend="swipeEnd($event)">
            <template x-if="current().type === 'image'">
                <button type="button" @click="openLightbox()" class="relative block w-full h-full overflow-hidden cursor-zoom-in bg-black" aria-label="Perbesar foto">
                    <img :src="current().src" :alt="current().caption || 'Foto dokumentasi'" class="w-full h-full object-cover object-center">
                </button>
            </template>
            <template x-if="current().type === 'video'">
                <x-steam-video />
            </template>
            <template x-if="current().type === 'youtube'">
                <iframe :src="current().src.replace('autoplay=1', 'autoplay=0')" title="Video YouTube" class="w-full h-full" allow="autoplay; encrypted-media; picture-in-picture; fullscreen" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe>
            </template>
            <div class="pointer-events-none absolute left-3 top-3 flex items-center gap-2">
                <span class="rounded-full bg-black/60 px-2.5 py-1 text-xs font-medium text-white" x-text="(index + 1) + ' / ' + items.length">1 / {{ $media->count() }}</span>
                <span x-show="current().type === 'youtube'" x-cloak class="rounded-full bg-black/60 px-2.5 py-1 text-xs font-medium text-white">YouTube</span>
            </div>
            <p x-show="current().caption && current().type === 'image'" x-text="current().caption" class="pointer-events-none absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/70 to-transparent px-4 pb-3 pt-8 text-sm font-medium text-white truncate"></p>
            @if ($media->count() > 1)
                <button type="button" @click="prev()" aria-label="Sebelumnya" class="absolute left-3 top-1/2 -translate-y-1/2 hidden sm:flex w-10 h-10 items-center justify-center rounded-full bg-black/40 text-white hover:bg-black/60 transition">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $chev['L'] }}"/></svg>
                </button>
                <button type="button" @click="next()" aria-label="Berikutnya" class="absolute right-3 top-1/2 -translate-y-1/2 hidden sm:flex w-10 h-10 items-center justify-center rounded-full bg-black/40 text-white hover:bg-black/60 transition">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $chev['R'] }}"/></svg>
                </button>
            @endif
        </div>

        {{-- Strip thumbnail --}}
        <div x-ref="track" class="flex gap-2 overflow-x-auto snap-x snap-mandatory scroll-smooth pb-1 [scrollbar-width:thin]">
            @foreach ($media as $i => $item)
                <button type="button" x-ref="thumb{{ $i }}" @click="select({{ $i }})"
                        :class="index === {{ $i }} ? 'ring-[3px] ring-orange-500 opacity-100' : 'opacity-75 hover:opacity-100'"
                        class="group relative shrink-0 snap-start w-28 sm:w-36 aspect-video rounded-md overflow-hidden bg-gray-200 ring-inset transition focus:outline-none focus-visible:ring-[3px] focus-visible:ring-orange-400"
                        aria-label="Tampilkan {{ strtolower($item->typeLabel()) }} {{ $i + 1 }}">
                    @if ($item->isVideo())
                        <video src="{{ $item->url() }}#t=0.5" preload="metadata" muted playsinline class="w-full h-full object-cover" @loadedmetadata="durations[{{ $i }}] = fmt($event.target.duration)"></video>
                    @else
                        <img src="{{ $item->thumbnailUrl() }}" alt="{{ $item->caption ?? 'Dokumentasi '.($i + 1) }}" loading="lazy" class="w-full h-full object-cover">
                    @endif
                    @unless ($item->isImage())
                        <x-gallery-play-icon class="w-8 h-8" />
                        <span class="absolute top-1 right-1 rounded bg-black/65 px-1.5 py-0.5 text-[10px] font-medium text-white">
                            @if ($item->isYoutube()) YouTube @else <span x-text="durations[{{ $i }}] || 'Video'">Video</span> @endif
                        </span>
                    @endunless
                </button>
            @endforeach
        </div>
        @if ($media->count() > 1)
            <div class="hidden sm:flex items-center gap-3">
                <button type="button" @click="scroll(-1)" aria-label="Geser ke kiri" class="w-8 h-6 flex items-center justify-center rounded-md border border-gray-200 bg-white text-gray-600 hover:text-orange-600 hover:border-orange-300 transition">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $chev['L'] }}"/></svg>
                </button>
                <div class="h-1.5 flex-1 rounded-full bg-gray-200 overflow-hidden">
                    <div class="h-full rounded-full bg-orange-500 transition-all" :style="'width:' + Math.max(8, ((index + 1) / items.length) * 100) + '%'"></div>
                </div>
                <button type="button" @click="scroll(1)" aria-label="Geser ke kanan" class="w-8 h-6 flex items-center justify-center rounded-md border border-gray-200 bg-white text-gray-600 hover:text-orange-600 hover:border-orange-300 transition">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $chev['R'] }}"/></svg>
                </button>
            </div>
            <div class="flex sm:hidden justify-center gap-1.5" aria-hidden="true">
                <template x-for="(it, i) in items" :key="i">
                    <span :class="i === index ? 'w-4 bg-orange-600' : 'w-1.5 bg-gray-300'" class="h-1.5 rounded-full transition-all"></span>
                </template>
            </div>
        @endif

        {{-- Lightbox layar penuh untuk foto --}}
        <template x-teleport="body">
            <div x-show="open" x-cloak x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/90 p-4" role="dialog" aria-modal="true" @click.self="close()">
                <button type="button" @click="close()" aria-label="Tutup" class="absolute top-4 right-4 w-10 h-10 rounded-full bg-white/10 text-white hover:bg-white/20 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                </button>
                <button type="button" x-show="items.length > 1" @click="prev()" aria-label="Sebelumnya" class="absolute left-3 sm:left-6 w-11 h-11 rounded-full bg-white/10 text-white hover:bg-white/20 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $chev['L'] }}"/></svg>
                </button>
                <figure class="max-w-5xl w-full text-center" @touchstart.passive="touchX = $event.touches[0].clientX" @touchend="swipeEnd($event)">
                    <template x-if="open && current().type === 'image'">
                        <img :src="current().src" :alt="current().caption || 'Foto dokumentasi'" class="mx-auto max-h-[80vh] rounded-lg object-contain">
                    </template>
                    <template x-if="open && current().type === 'video'">
                        <div class="mx-auto w-full max-w-4xl aspect-video overflow-hidden rounded-lg"><x-steam-video autoplay video-class="w-full h-full object-contain" /></div>
                    </template>
                    <template x-if="open && current().type === 'youtube'">
                        <div class="mx-auto w-full max-w-4xl aspect-video">
                            <iframe :src="current().src" title="Video YouTube" class="w-full h-full rounded-lg" allow="autoplay; encrypted-media; picture-in-picture; fullscreen" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe>
                        </div>
                    </template>
                    <figcaption class="mt-3 text-sm text-gray-200">
                        <span x-text="current().caption"></span>
                        <span class="block text-xs text-gray-400 mt-1" x-text="(index + 1) + ' dari ' + items.length"></span>
                    </figcaption>
                </figure>
                <button type="button" x-show="items.length > 1" @click="next()" aria-label="Berikutnya" class="absolute right-3 sm:right-6 w-11 h-11 rounded-full bg-white/10 text-white hover:bg-white/20 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $chev['R'] }}"/></svg>
                </button>
            </div>
        </template>
    @endif
</section>
