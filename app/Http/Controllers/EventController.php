<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Event;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string'],
            'city' => ['nullable', 'string'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $events = Event::published()
            ->where('end_at', '>=', now())
            ->filter($filters)
            ->with(['category', 'organization'])
            ->withCount('confirmedRegistrations')
            ->orderBy('start_at')
            ->paginate(9)
            ->withQueryString();

        return view('events.index', [
            'events' => $events,
            'filters' => $filters,
            'categories' => Category::orderBy('name')->get(),
            'cities' => Event::published()->distinct()->orderBy('city')->pluck('city'),
        ]);
    }

    public function show(Event $event)
    {
        abort_if($event->status === 'draft' && ! (auth()->user()?->can('update', $event)), 404);

        $event->load(['category', 'organization'])->loadCount('confirmedRegistrations');
        $myRegistration = auth()->check()
            ? $event->registrations()->where('user_id', auth()->id())->first()
            : null;

        return view('events.show', compact('event', 'myRegistration'));
    }
}
