<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventQuestion;
use App\Models\Registration;
use App\Services\RegistrationService;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use RuntimeException;

class RegistrationController extends Controller
{
    public function index(Request $request)
    {
        $registrations = $request->user()->registrations()
            ->with('event.category', 'event.organization', 'answers')
            ->latest()->get();

        return view('registrations.index', compact('registrations'));
    }


    public function create(Request $request, Event $event)
    {
        $data = $request->validate([
            'role' => ['nullable', 'in:relawan,donatur,peserta'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);
        $role = $data['role'] ?? array_key_first(Registration::TYPES);

        if ($event->status !== 'published') {
            return redirect()->route('events.show', $event)->with('error', 'Kegiatan ini tidak menerima pendaftaran.');
        }
        if ($event->end_at->isPast()) {
            return redirect()->route('events.show', $event)->with('error', 'Kegiatan ini sudah berakhir.');
        }

        $existing = $event->registrations()->where('user_id', $request->user()->id)->first();
        if ($existing && $existing->status === 'confirmed') {
            return redirect()->route('events.show', $event)->with('error', 'Anda sudah terdaftar pada kegiatan ini.');
        }

        if ($event->remainingQuota() <= 0) {
            return redirect()->route('events.show', $event)->with('error', 'Maaf, kuota kegiatan ini sudah penuh.');
        }

        $event->load(['category', 'organization', 'questions']);
        $questions = $event->questions;

        return view('registrations.create', [
            'event' => $event,
            'role' => $role,
            'note' => $data['note'] ?? old('note'),
            'questions' => $questions,
        ]);
    }

    public function store(Request $request, Event $event, RegistrationService $service)
    {
        $data = $request->validate([
            'type' => ['required', 'in:relawan,donatur,peserta'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        // Hanya pertanyaan untuk peran yang dipilih yang divalidasi dan disimpan.
        $questions = $event->questions()->get()->filter(fn (EventQuestion $q) => $q->appliesTo($data['type']))->values();
        $rules = ['answers' => ['nullable', 'array']];
        $attributes = [];
        foreach ($questions as $q) {
            $rules['answers.'.$q->id] = $q->answerRules();
            $attributes['answers.'.$q->id] = Str::lower($q->label);
        }
        $validated = $request->validate($rules, [
            'answers.*.in' => 'Pilihan :attribute tidak valid.',
        ], $attributes);

        $answers = $questions->map(fn (EventQuestion $q) => [
            'question' => $q,
            'value' => $q->formatAnswer($validated['answers'][$q->id] ?? null),
        ])->all();

        try {
            $service->register($request->user(), $event, $data['type'], $data['note'] ?? null, $answers);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        } catch (LockTimeoutException) {
            return back()->with('error', 'Server sedang sibuk, silakan coba lagi.');
        }

        return redirect()->route('registrations.index')->with('success', 'Pendaftaran berhasil! Sampai jumpa di kegiatan "'.$event->title.'".');
    }


    public function ticket(Request $request, Registration $registration)
    {
        Gate::authorize('viewTicket', $registration);
        $registration->load(['event.category', 'answers']);

        return view('registrations.ticket', [
            'registration' => $registration,
            'autoprint' => $request->boolean('autoprint'),
        ]);
    }

    public function destroy(Request $request, Registration $registration)
    {
        Gate::authorize('cancel', $registration);
        $registration->update(['status' => 'cancelled']);

        return back()->with('success', 'Pendaftaran dibatalkan.');
    }
}
