@props(['user'])
@if ($user->avatarUrl())
    <img src="{{ $user->avatarUrl() }}" alt="Foto profil {{ $user->name }}" {{ $attributes->merge(['class' => 'rounded-full object-cover shrink-0']) }}>
@else
    <span {{ $attributes->merge(['class' => 'rounded-full bg-orange-100 text-orange-700 font-semibold flex items-center justify-center shrink-0']) }} aria-label="Inisial {{ $user->name }}">{{ $user->initials() }}</span>
@endif
