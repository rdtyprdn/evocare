<?php

namespace App\Http\Controllers\Organizer;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Event;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $events = Event::query()
            ->when(! $user->isAdmin(), fn ($q) => $q->where('user_id', $user->id))
            ->with('category')->withCount('confirmedRegistrations')
            ->orderByDesc('start_at')->paginate(15);

        return view('organizer.events.index', compact('events'));
    }

    public function create()
    {
        Gate::authorize('create', Event::class);

        return view('organizer.events.form', ['event' => new Event(['status' => 'published', 'quota' => 50]), 'categories' => Category::orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        Gate::authorize('create', Event::class);
        $data = $this->validated($request);
        $user = $request->user();

        if (! $user->organization_id) {
            $org = Organization::create(['name' => $user->name, 'slug' => Str::slug($user->name).'-'.Str::lower(Str::random(4)), 'type' => 'komunitas']);
            $user->update(['organization_id' => $org->id]);
        }

        $data['user_id'] = $user->id;
        $data['organization_id'] = $user->organization_id;
        $data['slug'] = Str::slug($data['title']).'-'.Str::lower(Str::random(5));
        Event::create($data);

        return redirect()->route('organizer.events.index')->with('success', 'Kegiatan berhasil dibuat.');
    }

    public function edit(Event $event)
    {
        Gate::authorize('update', $event);

        return view('organizer.events.form', ['event' => $event, 'categories' => Category::orderBy('name')->get()]);
    }

    public function update(Request $request, Event $event)
    {
        Gate::authorize('update', $event);
        $data = $this->validated($request);

        $confirmed = $event->confirmedRegistrations()->count();
        if ($data['quota'] < $confirmed) {
            return back()->withInput()->withErrors(['quota' => "Kuota tidak boleh kurang dari jumlah pendaftar saat ini ({$confirmed})."]);
        }

        $event->update($data);

        return redirect()->route('organizer.events.index')->with('success', 'Kegiatan berhasil diperbarui.');
    }

    public function destroy(Event $event)
    {
        Gate::authorize('delete', $event);
        $event->delete();

        return redirect()->route('organizer.events.index')->with('success', 'Kegiatan dihapus.');
    }

    public function registrants(Event $event)
    {
        Gate::authorize('viewRegistrants', $event);
        $registrations = $event->registrations()->with('user')->latest()->get();

        return view('organizer.events.registrants', compact('event', 'registrations'));
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'category_id' => ['required', 'exists:categories,id'],
            'description' => ['required', 'string'],
            'city' => ['required', 'string', 'max:80'],
            'location' => ['required', 'string', 'max:200'],
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after:start_at'],
            'quota' => ['required', 'integer', 'min:1', 'max:100000'],
            'image_url' => ['nullable', 'url', 'max:500'],
            'status' => ['required', 'in:draft,published,cancelled'],
        ], [], [
            'title' => 'judul', 'category_id' => 'kategori', 'description' => 'deskripsi', 'city' => 'kota',
            'location' => 'lokasi', 'start_at' => 'waktu mulai', 'end_at' => 'waktu selesai', 'quota' => 'kuota',
        ]);
    }
}
