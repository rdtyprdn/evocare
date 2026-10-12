<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrganizationController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'pending');
        $organizations = Organization::with('owner')->withCount('events')
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->latest()->get();

        return view('admin.organizations.index', [
            'organizations' => $organizations,
            'status' => $status,
            'counts' => Organization::selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status'),
        ]);
    }

    public function verify(Organization $organization)
    {
        DB::transaction(function () use ($organization) {
            $organization->update(['status' => 'verified', 'verified_at' => now(), 'admin_note' => null]);

            if ($owner = $organization->owner) {
                $owner->update([
                    'organization_id' => $organization->id,
                    'role' => $owner->isAdmin() ? 'admin' : 'organizer',
                ]);
                // Kegiatan lama milik pemilik yang belum punya organisasi dihubungkan ke organisasinya sendiri.
                Event::where('user_id', $owner->id)->whereNull('organization_id')->update(['organization_id' => $organization->id]);
            }
        });

        return back()->with('success', "Organisasi \"{$organization->name}\" terverifikasi.");
    }

    public function reject(Request $request, Organization $organization)
    {
        $data = $request->validate(['admin_note' => ['required', 'string', 'max:1000']], [], ['admin_note' => 'alasan penolakan']);
        $organization->update(['status' => 'rejected', 'verified_at' => null, 'admin_note' => $data['admin_note']]);

        return back()->with('success', "Organisasi \"{$organization->name}\" ditolak.");
    }
}
