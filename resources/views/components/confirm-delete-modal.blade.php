@props([
    'action',
    'title' => 'Konfirmasi Penghapusan',
    'description' => 'Apakah Anda yakin ingin menghapus data ini? Tindakan ini tidak dapat dibatalkan.',
    'itemName' => null,
    'itemMeta' => null,
    'triggerLabel' => 'Hapus',
    'triggerClass' => 'text-red-600 hover:underline',
    'confirmLabel' => 'Ya, Hapus',
    'cancelLabel' => 'Batal',
])

<div x-data="{ open: false, submitting: false }" @keydown.escape.window="if (!submitting) open = false" class="inline">
    <button type="button" @click="open = true" class="{{ $triggerClass }}">{{ $triggerLabel }}</button>

    <template x-teleport="body">
        <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true">
            <div x-show="open"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="if (!submitting) open = false"
                 class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm"></div>

            <div x-show="open"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-3 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-3 sm:scale-95"
                 class="relative w-full max-w-md rounded-2xl bg-white shadow-2xl border border-gray-100 overflow-hidden text-left">
                <div class="p-6 space-y-4">
                    <div class="flex items-start gap-3.5">
                        <span class="w-10 h-10 rounded-xl bg-red-50 border border-red-100 text-red-600 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg>
                        </span>
                        <div class="space-y-1">
                            <h3 class="text-base font-bold text-gray-900">{{ $title }}</h3>
                            <p class="text-sm text-gray-600 whitespace-normal">{{ $description }}</p>
                        </div>
                    </div>

                    @if ($itemName)
                        <div class="rounded-xl bg-gray-50 border border-gray-200/80 p-3.5">
                            <p class="font-semibold text-sm text-gray-900 whitespace-normal">{{ $itemName }}</p>
                            @if ($itemMeta)
                                <p class="text-xs text-gray-500 mt-0.5 whitespace-normal">{{ $itemMeta }}</p>
                            @endif
                        </div>
                    @endif

                    <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2.5 pt-2">
                        <button type="button" @click="open = false" :disabled="submitting" class="rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                            {{ $cancelLabel }}
                        </button>
                        <form method="POST" action="{{ $action }}" @submit="submitting = true" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" :disabled="submitting" class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700 transition disabled:opacity-60">
                                <span x-text="submitting ? 'Memproses...' : @js($confirmLabel)">{{ $confirmLabel }}</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
