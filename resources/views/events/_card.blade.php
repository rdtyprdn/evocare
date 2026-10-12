<a href="{{ route('events.show', $event) }}" class="group bg-white rounded-xl shadow-sm hover:shadow-md overflow-hidden flex flex-col transition">
    <x-event-image :event="$event" class="h-44" />
    <div class="p-4 flex-1 flex flex-col">
        <span class="text-xs font-semibold uppercase text-orange-600">{{ $event->category->name }}</span>
        <h3 class="mt-1 font-semibold text-lg group-hover:text-orange-600">{{ $event->title }}</h3>
        <p class="text-sm text-orange-700 mt-1">{{ $event->start_at->translatedFormat('D, d M Y · H:i') }}</p>
        <p class="text-sm text-gray-500">{{ $event->location }}, {{ $event->city }}</p>
        <p class="text-sm text-gray-500 mt-auto pt-3">oleh <span class="font-medium">{{ $event->organization->name }}</span></p>
        @php $sisa = $event->remainingQuota(); @endphp
        <p class="text-xs mt-1 {{ $sisa ? 'text-green-700' : 'text-red-600' }}">{{ $sisa ? "Sisa kuota: {$sisa} / {$event->quota}" : 'Kuota penuh' }}</p>
    </div>
</a>
