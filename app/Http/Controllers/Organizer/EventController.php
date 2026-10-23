<?php

namespace App\Http\Controllers\Organizer;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Event;
use App\Models\Registration;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EventController extends Controller
{
    private function organizerEventsQuery(Request $request): Builder
    {
        $user = $request->user();

        return Event::query()
            ->when(! $user->isAdmin(), fn (Builder $q) => $q->where('user_id', $user->id));
    }

    private function withRegistrationCounts(Builder $query): Builder
    {
        return $query->withCount([
            'confirmedRegistrations',
            'registrations as cancelled_registrations_count' => fn (Builder $q) => $q->where('status', 'cancelled'),
            'registrations as relawan_count' => fn (Builder $q) => $q->where('status', 'confirmed')->where('type', 'relawan'),
            'registrations as donatur_count' => fn (Builder $q) => $q->where('status', 'confirmed')->where('type', 'donatur'),
            'registrations as peserta_count' => fn (Builder $q) => $q->where('status', 'confirmed')->where('type', 'peserta'),
        ]);
    }

    private function buildStats(Request $request): array
    {
        $allEvents = $this->withRegistrationCounts($this->organizerEventsQuery($request))->get();
        $now = now();

        $totalQuota = (int) $allEvents->sum('quota');
        $confirmed = (int) $allEvents->sum('confirmed_registrations_count');
        $cancelled = (int) $allEvents->sum('cancelled_registrations_count');
        $relawan = (int) $allEvents->sum('relawan_count');
        $donatur = (int) $allEvents->sum('donatur_count');
        $peserta = (int) $allEvents->sum('peserta_count');

        return [
            'total_events' => $allEvents->count(),
            'published_events' => $allEvents->where('status', 'published')->count(),
            'upcoming_events' => $allEvents->where('status', 'published')->filter(fn (Event $e) => $e->end_at && $e->end_at->gte($now))->count(),
            'completed_events' => $allEvents->where('status', 'published')->filter(fn (Event $e) => $e->end_at && $e->end_at->lt($now))->count(),
            'draft_cancelled_events' => $allEvents->whereIn('status', ['draft', 'cancelled'])->count(),
            'total_quota' => $totalQuota,
            'confirmed_registrations' => $confirmed,
            'cancelled_registrations' => $cancelled,
            'occupancy_rate' => $totalQuota > 0 ? (int) min(100, round(($confirmed / $totalQuota) * 100)) : 0,
            'roles' => [
                'relawan' => $relawan,
                'donatur' => $donatur,
                'peserta' => $peserta,
            ],
        ];
    }

    public function index(Request $request)
    {
        $user = $request->user();
        if (! $user->canManageEvents()) {
            return redirect()->route('organizations.create');
        }

        $stats = $this->buildStats($request);
        $events = $this->withRegistrationCounts($this->organizerEventsQuery($request))
            ->with('category', 'organization')
            ->orderByDesc('start_at')
            ->paginate(15);

        return view('organizer.events.index', compact('events', 'stats'));
    }

    public function schedule(Request $request)
    {
        $user = $request->user();
        if (! $user->canManageEvents()) {
            return redirect()->route('organizations.create');
        }

        $allEvents = $this->withRegistrationCounts($this->organizerEventsQuery($request))
            ->with('category', 'organization')
            ->orderBy('start_at')
            ->get();

        $availableMonths = $allEvents
            ->filter(fn (Event $e) => $e->start_at !== null)
            ->mapWithKeys(fn (Event $e) => [$e->start_at->format('Y-m') => $e->start_at->translatedFormat('F Y')])
            ->all();

        $period = $request->query('period', 'all');
        $month = $request->query('month', '');
        $status = $request->query('status', 'all');

        $filteredEvents = $allEvents->filter(function (Event $event) use ($period, $month, $status) {
            if ($status !== 'all' && $event->status !== $status) {
                return false;
            }
            if ($month !== '' && $event->start_at?->format('Y-m') !== $month) {
                return false;
            }
            if ($period !== 'all' && $event->executionState() !== $period) {
                return false;
            }

            return true;
        })->values();

        // Deteksi bentrok jadwal antar kegiatan aktif pada daftar yang ditampilkan
        $activeEvents = $filteredEvents->where('status', '!=', 'cancelled')->values();
        $conflicts = [];
        $conflictPairs = [];
        for ($i = 0; $i < $activeEvents->count(); $i++) {
            for ($j = $i + 1; $j < $activeEvents->count(); $j++) {
                $a = $activeEvents[$i];
                $b = $activeEvents[$j];
                if ($a->start_at && $a->end_at && $b->start_at && $b->end_at && $a->start_at->lt($b->end_at) && $b->start_at->lt($a->end_at)) {
                    $conflicts[$a->id][] = $b;
                    $conflicts[$b->id][] = $a;
                    $conflictPairs[] = [$a, $b];
                }
            }
        }

        $groupedByMonth = $filteredEvents->groupBy(fn (Event $e) => $e->start_at?->format('Y-m') ?? 'tanpa-jadwal');

        return view('organizer.events.schedule', [
            'events' => $filteredEvents,
            'groupedByMonth' => $groupedByMonth,
            'availableMonths' => $availableMonths,
            'conflicts' => $conflicts,
            'conflictPairs' => $conflictPairs,
            'period' => $period,
            'month' => $month,
            'status' => $status,
            'stats' => $this->buildStats($request),
        ]);
    }

    public function reports(Request $request)
    {
        $user = $request->user();
        if (! $user->canManageEvents()) {
            return redirect()->route('organizations.create');
        }

        $period = $request->query('period', 'all');
        $status = $request->query('status', 'all');

        $allEvents = $this->withRegistrationCounts($this->organizerEventsQuery($request))
            ->with('category', 'organization')
            ->orderByDesc('start_at')
            ->get();

        $events = $allEvents->filter(function (Event $event) use ($period, $status) {
            if ($status !== 'all' && $event->status !== $status) {
                return false;
            }
            if ($period !== 'all' && $event->executionState() !== $period) {
                return false;
            }

            return true;
        })->values();

        $stats = $this->buildStats($request);
        $documentedCount = $allEvents->filter(fn (Event $e) => filled($e->reportSummary()))->count();

        return view('organizer.events.reports', compact('events', 'stats', 'period', 'status', 'documentedCount'));
    }

    public function eventReport(Event $event)
    {
        Gate::authorize('viewRegistrants', $event);

        $event->load('category', 'organization');
        $registrations = $event->registrations()->with('user')->latest()->get();
        $confirmed = $registrations->where('status', 'confirmed');
        $cancelled = $registrations->where('status', 'cancelled');

        $reportStats = [
            'quota' => $event->quota,
            'confirmed' => $confirmed->count(),
            'cancelled' => $cancelled->count(),
            'remaining' => max(0, $event->quota - $confirmed->count()),
            'occupancy' => $event->quota > 0 ? (int) min(100, round(($confirmed->count() / $event->quota) * 100)) : 0,
            'roles' => [
                'relawan' => $confirmed->where('type', 'relawan')->count(),
                'donatur' => $confirmed->where('type', 'donatur')->count(),
                'peserta' => $confirmed->where('type', 'peserta')->count(),
            ],
        ];

        return view('organizer.events.report_show', compact('event', 'registrations', 'reportStats'));
    }

    public function updateReport(Request $request, Event $event)
    {
        Gate::authorize('update', $event);

        $data = $request->validate([
            'report_summary' => ['nullable', 'string', 'max:5000'],
        ], [], [
            'report_summary' => 'catatan laporan kegiatan',
        ]);

        $event->saveReport($data['report_summary'] ?? null);

        return redirect()->route('organizer.events.report', $event)->with('success', 'Laporan kegiatan berhasil disimpan.');
    }

    public function create(Request $request)
    {
        if (! $request->user()->hasVerifiedOrganization()) {
            return redirect()->route('organizations.create')->with('error', 'Anda perlu mendaftarkan organisasi dan menunggu verifikasi admin sebelum membuat kegiatan.');
        }
        Gate::authorize('create', Event::class);

        return view('organizer.events.form', ['event' => new Event(['status' => 'published', 'quota' => 50]), 'categories' => Category::orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        Gate::authorize('create', Event::class);
        $data = $this->validated($request);
        $user = $request->user();

        $data['user_id'] = $user->id;
        $data['organization_id'] = $user->organization->id; // selalu organisasi milik pembuat
        $data['slug'] = Str::slug($data['title']).'-'.Str::lower(Str::random(5));
        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('events', 'public');
        }
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

        if ($data['status'] === 'published' && $event->status !== 'published' && ! $request->user()->hasVerifiedOrganization() && ! $request->user()->isAdmin()) {
            return back()->withInput()->withErrors(['status' => 'Kegiatan hanya dapat dipublikasikan setelah organisasi Anda terverifikasi.']);
        }

        $confirmed = $event->confirmedRegistrations()->count();
        if ($data['quota'] < $confirmed) {
            return back()->withInput()->withErrors(['quota' => "Kuota tidak boleh kurang dari jumlah pendaftar saat ini ({$confirmed})."]);
        }

        $old = $event->image_path;
        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('events', 'public');
        } elseif ($request->boolean('remove_image')) {
            $data['image_path'] = null;
        }
        $event->update($data);
        if ($old && $old !== $event->image_path) {
            Storage::disk('public')->delete($old);
        }

        return redirect()->route('organizer.events.index')->with('success', 'Kegiatan berhasil diperbarui.');
    }

    public function destroy(Event $event)
    {
        Gate::authorize('delete', $event);
        $path = $event->image_path;
        $event->delete();
        if ($path) {
            Storage::disk('public')->delete($path);
        }

        return redirect()->route('organizer.events.index')->with('success', 'Kegiatan dihapus.');
    }

    public function registrants(Request $request, Event $event)
    {
        Gate::authorize('viewRegistrants', $event);

        $allRegistrations = $event->registrations()->with('user')->latest()->get();
        $type = $request->query('type', 'all');
        $status = $request->query('status', 'all');

        $registrations = $allRegistrations->filter(function (Registration $r) use ($type, $status) {
            if ($type !== 'all' && $r->type !== $type) {
                return false;
            }
            if ($status !== 'all' && $r->status !== $status) {
                return false;
            }

            return true;
        })->values();

        $confirmed = $allRegistrations->where('status', 'confirmed');
        $roleCounts = [
            'all' => $allRegistrations->count(),
            'confirmed' => $confirmed->count(),
            'cancelled' => $allRegistrations->where('status', 'cancelled')->count(),
            'relawan' => $confirmed->where('type', 'relawan')->count(),
            'donatur' => $confirmed->where('type', 'donatur')->count(),
            'peserta' => $confirmed->where('type', 'peserta')->count(),
        ];

        return view('organizer.events.registrants', compact('event', 'registrations', 'roleCounts', 'type', 'status'));
    }

    public function exportRegistrants(Request $request, Event $event): StreamedResponse
    {
        Gate::authorize('viewRegistrants', $event);

        $type = $request->query('type', 'all');
        $status = $request->query('status', 'all');

        $registrations = $event->registrations()
            ->with('user')
            ->when($type !== 'all', fn (Builder $q) => $q->where('type', $type))
            ->when($status !== 'all', fn (Builder $q) => $q->where('status', $status))
            ->orderBy('created_at')
            ->get();

        $filename = 'laporan-pendaftar-'.$event->slug.'.csv';

        return response()->streamDownload(function () use ($event, $registrations) {
            $out = fopen('php://output', 'w');
            // UTF-8 BOM agar terbaca rapi di Excel / Spreadsheet
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Laporan Kegiatan', $event->title]);
            fputcsv($out, ['Kota / Lokasi', $event->city.' - '.$event->location]);
            fputcsv($out, ['Jadwal Pelaksanaan', $event->start_at?->format('d M Y H:i').' s.d. '.$event->end_at?->format('d M Y H:i')]);
            fputcsv($out, ['Kuota Kegiatan', (string) $event->quota]);
            if (filled($event->reportSummary())) {
                fputcsv($out, ['Catatan Laporan Kegiatan', $event->reportSummary()]);
            }
            fputcsv($out, []);
            fputcsv($out, ['No', 'Nama Pendaftar', 'Email', 'Peran Partisipasi', 'Status Pendaftaran', 'Catatan', 'Tanggal Daftar']);

            foreach ($registrations as $idx => $r) {
                fputcsv($out, [
                    $idx + 1,
                    $r->user?->name ?? '-',
                    $r->user?->email ?? '-',
                    Registration::TYPES[$r->type] ?? $r->type,
                    $r->status === 'confirmed' ? 'Terkonfirmasi' : 'Dibatalkan',
                    $r->note ?? '',
                    $r->created_at?->format('Y-m-d H:i:s') ?? '',
                ]);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'category_id' => ['required', 'exists:categories,id'],
            'description' => ['required', 'string'],
            'city' => ['required', 'string', 'max:80'],
            'location' => ['required', 'string', 'max:200'],
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after:start_at'],
            'quota' => ['required', 'integer', 'min:1', 'max:100000'],
            'image_url' => ['nullable', 'url', 'max:500'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_image' => ['nullable', 'boolean'],
            'status' => ['required', 'in:draft,published,cancelled'],
        ], [], [
            'title' => 'judul', 'category_id' => 'kategori', 'description' => 'deskripsi', 'city' => 'kota',
            'location' => 'lokasi', 'start_at' => 'waktu mulai', 'end_at' => 'waktu selesai', 'quota' => 'kuota',
            'image' => 'gambar kegiatan',
        ]);

        unset($data['image'], $data['remove_image']);

        return $data;
    }
}
