@props(['event', 'size' => 'sm'])
@php $org = $event->organization; @endphp
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2 flex-wrap']) }}>
    @if ($org)
        <x-org-logo :org="$org" class="{{ $size === 'lg' ? 'w-10 h-10 text-sm' : 'w-6 h-6 text-[10px]' }}" />
        <span class="font-medium text-gray-700">{{ $org->name }}</span>
        @if ($org->isVerified())<x-verified-badge />@endif
    @else
        <span class="font-medium text-gray-700">{{ $event->creator?->name ?? 'Penyelenggara' }}</span>
        <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-500">Organisasi belum terverifikasi</span>
    @endif
</span>
