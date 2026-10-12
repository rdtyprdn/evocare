<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        return match ($request->user()->role) {
            'admin' => redirect()->route('admin.categories.index'),
            'organizer' => redirect()->route('organizer.events.index'),
            default => redirect()->route('registrations.index'),
        };
    }
}
