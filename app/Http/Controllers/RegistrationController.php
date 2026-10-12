<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Registration;
use App\Services\RegistrationService;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use RuntimeException;

class RegistrationController extends Controller
{
    public function index(Request $request)
    {
        $registrations = $request->user()->registrations()
            ->with('event.category', 'event.organization')
            ->latest()->get();

        return view('registrations.index', compact('registrations'));
    }

    public function store(Request $request, Event $event, RegistrationService $service)
    {
        $data = $request->validate([
            'type' => ['required', 'in:relawan,donatur,peserta'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $service->register($request->user(), $event, $data['type'], $data['note'] ?? null);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        } catch (LockTimeoutException) {
            return back()->with('error', 'Server sedang sibuk, silakan coba lagi.');
        }

        return redirect()->route('registrations.index')->with('success', 'Pendaftaran berhasil! Sampai jumpa di kegiatan "'.$event->title.'".');
    }

    public function destroy(Request $request, Registration $registration)
    {
        Gate::authorize('cancel', $registration);
        $registration->update(['status' => 'cancelled']);

        return back()->with('success', 'Pendaftaran dibatalkan.');
    }
}
