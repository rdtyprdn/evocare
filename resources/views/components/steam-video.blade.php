@props(['src' => 'current().src', 'autoplay' => false, 'videoClass' => 'w-full h-full'])
{{-- Pemutar video khusus (gaya Steam): kontrol sendiri, tanpa menu unduh bawaan browser. --}}
<div x-data="{
        playing: false, muted: false, volume: 1, cur: 0, dur: 0, buffered: 0, show: true, hideT: null, seeking: false, fs: false,
        v() { return this.$refs.vid; },
        t(s) { if (! s || ! isFinite(s)) return '0:00'; s = Math.floor(s); const h = Math.floor(s / 3600), m = Math.floor((s % 3600) / 60), x = String(s % 60).padStart(2, '0'); return h ? h + ':' + String(m).padStart(2, '0') + ':' + x : m + ':' + x; },
        toggle() { const v = this.v(); v.paused || v.ended ? v.play().catch(() => {}) : v.pause(); this.poke(); },
        mute() { const v = this.v(); v.muted = ! v.muted; if (! v.muted && v.volume === 0) v.volume = 0.5; this.poke(); },
        setVolume(x) { const v = this.v(); v.volume = Math.min(1, Math.max(0, Math.round(x * 100) / 100)); v.muted = v.volume === 0; },
        volFrom(e) { const r = this.$refs.volbar.getBoundingClientRect(); this.setVolume(((e.touches ? e.touches[0].clientX : e.clientX) - r.left) / r.width); },
        startVol(e) { this.volFrom(e); const mv = (ev) => this.volFrom(ev); const up = () => { window.removeEventListener('pointermove', mv); window.removeEventListener('pointerup', up); }; window.addEventListener('pointermove', mv); window.addEventListener('pointerup', up); },
        seekTo(e) { const r = this.$refs.bar.getBoundingClientRect(); const x = (e.touches ? e.touches[0].clientX : e.clientX) - r.left; const p = Math.min(1, Math.max(0, x / r.width)); if (this.dur) { this.v().currentTime = p * this.dur; this.cur = this.v().currentTime; } },
        startSeek(e) { this.seeking = true; this.seekTo(e); const mv = (ev) => this.seekTo(ev); const up = () => { this.seeking = false; window.removeEventListener('pointermove', mv); window.removeEventListener('pointerup', up); }; window.addEventListener('pointermove', mv); window.addEventListener('pointerup', up); },
        fullscreen() { const el = this.$root; if (document.fullscreenElement) document.exitFullscreen(); else if (el.requestFullscreen) el.requestFullscreen().catch(() => {}); else if (this.v().webkitEnterFullscreen) this.v().webkitEnterFullscreen(); },
        poke() { this.show = true; clearTimeout(this.hideT); if (this.playing) this.hideT = setTimeout(() => { if (! this.seeking) this.show = false; }, 2500); },
        sync() { const v = this.v(); this.cur = v.currentTime; this.dur = v.duration || 0; if (v.buffered.length) this.buffered = v.buffered.end(v.buffered.length - 1); },
     }"
     x-init="$watch('playing', p => { if (! p) { clearTimeout(hideT); show = true; } else poke(); })"
     @fullscreenchange.document="fs = document.fullscreenElement === $root"
     @mousemove="poke()" @touchstart.passive="poke()" @mouseleave="if (playing) show = false"
     @keydown.space.prevent.stop="toggle()" @keydown.k.stop="toggle()" @keydown.m.stop="mute()" @keydown.f.stop="fullscreen()"
     tabindex="0" role="region" aria-label="Pemutar video"
     class="group/player relative w-full h-full bg-black select-none outline-none focus-visible:ring-2 focus-visible:ring-orange-500"
     :class="fs ? 'flex items-center' : ''"
     @contextmenu.prevent>
    <video x-ref="vid" data-gallery-player :src="{{ $src }}" playsinline preload="metadata" @if ($autoplay) autoplay @endif
           controlsList="nodownload noremoteplayback noplaybackrate" disablePictureInPicture disableRemotePlayback draggable="false"
           @contextmenu.prevent @dragstart.prevent
           @click="toggle()"
           @play="playing = true" @pause="playing = false" @ended="playing = false"
           @loadedmetadata="sync(); muted = $el.muted; volume = $el.volume" @timeupdate="if (! seeking) sync()" @progress="sync()"
           @volumechange="muted = $el.muted; volume = $el.volume"
           class="{{ $videoClass }} bg-black cursor-pointer"></video>

    {{-- Tombol putar besar saat jeda --}}
    <button type="button" x-show="! playing" x-transition.opacity @click="toggle()" aria-label="Putar video"
            class="absolute inset-0 m-auto flex h-16 w-16 items-center justify-center rounded-full bg-black/55 text-white ring-1 ring-white/25 backdrop-blur-sm hover:bg-orange-600 hover:ring-orange-400 hover:scale-105 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-orange-500 transition">
        <svg class="h-7 w-7 translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.65c0-.86.92-1.4 1.67-.98l11.54 6.35a1.13 1.13 0 0 1 0 1.96L6.92 19.33c-.75.42-1.67-.12-1.67-.98V5.65Z"/></svg>
    </button>

    {{-- Bilah kontrol --}}
    <div x-show="show || ! playing" x-transition.opacity.duration.200ms @click.stop @touchstart.stop @touchend.stop
         class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/85 via-black/45 to-transparent px-3 pb-2 pt-8 text-white">
        <div x-ref="bar" @pointerdown.prevent="startSeek($event)" role="slider" aria-label="Posisi video" :aria-valuenow="Math.round(cur)" aria-valuemin="0" :aria-valuemax="Math.round(dur)" tabindex="0"
             @keydown.right.prevent.stop="v().currentTime = Math.min(dur, cur + 5)" @keydown.left.prevent.stop="v().currentTime = Math.max(0, cur - 5)"
             class="group/bar relative mb-2 h-4 cursor-pointer touch-none flex items-center rounded outline-none focus-visible:ring-2 focus-visible:ring-orange-500">
            <div class="relative h-[3px] w-full rounded-full bg-white/20 group-hover/bar:h-1.5 transition-all duration-150" :class="seeking && '!h-1.5'">
                <div class="absolute inset-y-0 left-0 rounded-full bg-white/20" :style="'width:' + (dur ? buffered / dur * 100 : 0) + '%'"></div>
                <div class="absolute inset-y-0 left-0 rounded-full bg-orange-600" :style="'width:' + (dur ? cur / dur * 100 : 0) + '%'"></div>
                <div class="absolute top-1/2 h-3 w-3 -translate-x-1/2 -translate-y-1/2 rounded-full bg-orange-500 ring-2 ring-white/90 shadow scale-75 opacity-0 group-hover/bar:scale-100 group-hover/bar:opacity-100 transition" :class="seeking && 'opacity-100'" :style="'left:' + (dur ? cur / dur * 100 : 0) + '%'"></div>
            </div>
        </div>
        <div class="flex items-center gap-2 sm:gap-3 text-sm">
            <button type="button" @click="toggle()" :aria-label="playing ? 'Jeda' : 'Putar'" class="p-1.5 rounded hover:bg-white/15 hover:text-orange-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-orange-500">
                <svg x-show="! playing" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.65c0-.86.92-1.4 1.67-.98l11.54 6.35a1.13 1.13 0 0 1 0 1.96L6.92 19.33c-.75.42-1.67-.12-1.67-.98V5.65Z"/></svg>
                <svg x-show="playing" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25v13.5m-7.5-13.5v13.5"/></svg>
            </button>
            <div class="group/vol flex items-center mr-1">
                <button type="button" @click="mute()" :aria-label="muted || volume === 0 ? 'Bunyikan' : 'Bisukan'" class="p-1.5 rounded hover:bg-white/15 hover:text-orange-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-orange-500">
                    <svg x-show="! muted && volume > 0" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M19.11 4.89a9.75 9.75 0 0 1 0 14.22M16.46 7.54a6 6 0 0 1 0 8.92M6.75 8.25l4.72-4.72a.75.75 0 0 1 1.28.53v15.88a.75.75 0 0 1-1.28.53l-4.72-4.72H4.51c-.88 0-1.7-.51-1.95-1.36a9.8 9.8 0 0 1 0-5.78c.25-.85 1.07-1.36 1.95-1.36h2.24Z"/></svg>
                    <svg x-show="muted || volume === 0" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M17.25 9.75 19.5 12m0 0 2.25 2.25M19.5 12l2.25-2.25M19.5 12l-2.25 2.25m-10.5-6 4.72-4.72a.75.75 0 0 1 1.28.53v15.88a.75.75 0 0 1-1.28.53l-4.72-4.72H4.51c-.88 0-1.7-.51-1.95-1.36a9.8 9.8 0 0 1 0-5.78c.25-.85 1.07-1.36 1.95-1.36h2.24Z"/></svg>
                </button>
                <div class="hidden sm:flex items-center overflow-hidden w-0 opacity-0 group-hover/vol:w-[5.5rem] group-hover/vol:opacity-100 group-focus-within/vol:w-[5.5rem] group-focus-within/vol:opacity-100 transition-all duration-200">
                    <div x-ref="volbar" @pointerdown.prevent="startVol($event)" role="slider" aria-label="Volume" tabindex="0"
                         aria-valuemin="0" aria-valuemax="100" :aria-valuenow="Math.round((muted ? 0 : volume) * 100)"
                         @keydown.right.prevent.stop="setVolume(volume + 0.1)" @keydown.left.prevent.stop="setVolume(volume - 0.1)"
                         class="relative ml-1.5 mr-2 h-4 w-[4.5rem] flex items-center cursor-pointer touch-none rounded outline-none focus-visible:ring-2 focus-visible:ring-orange-500">
                        <div class="relative h-[3px] w-full rounded-full bg-white/25">
                            <div class="absolute inset-y-0 left-0 rounded-full bg-orange-600" :style="'width:' + (muted ? 0 : volume) * 100 + '%'"></div>
                            <div class="absolute top-1/2 h-2.5 w-2.5 -translate-x-1/2 -translate-y-1/2 rounded-full bg-orange-500 ring-2 ring-white/90 shadow" :style="'left:' + (muted ? 0 : volume) * 100 + '%'"></div>
                        </div>
                    </div>
                </div>
            </div>
            <span class="tabular-nums text-xs sm:text-sm text-white/90"><span x-text="t(cur)">0:00</span> / <span x-text="t(dur)">0:00</span></span>
            <button type="button" @click="fullscreen()" :aria-label="fs ? 'Keluar layar penuh' : 'Layar penuh'" class="ml-auto p-1.5 rounded hover:bg-white/15 hover:text-orange-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-orange-500">
                <svg x-show="! fs" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15M20.25 3.75h-4.5m4.5 0v4.5m0-4.5L15 9m5.25 11.25h-4.5m4.5 0v-4.5m0 4.5L15 15"/></svg>
                <svg x-show="fs" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 9V4.5M9 9H4.5M9 9 3.75 3.75M9 15v4.5M9 15H4.5M9 15l-5.25 5.25M15 9h4.5M15 9V4.5M15 9l5.25-5.25M15 15h4.5M15 15v4.5m0-4.5 5.25 5.25"/></svg>
            </button>
        </div>
    </div>
</div>
