@props(['org'])
@if ($org?->logoUrl())
    <img src="{{ $org->logoUrl() }}" alt="Logo {{ $org->name }}" {{ $attributes->merge(['class' => 'rounded-full object-cover shrink-0 bg-white border']) }}>
@else
    <span {{ $attributes->merge(['class' => 'rounded-full bg-gray-100 text-gray-500 font-semibold flex items-center justify-center shrink-0']) }} aria-hidden="true">{{ $org ? mb_strtoupper(mb_substr($org->name, 0, 1)) : '?' }}</span>
@endif
