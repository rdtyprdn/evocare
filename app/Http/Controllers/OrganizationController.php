<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/** Pendaftaran organisasi penyelenggara oleh pengguna. */
class OrganizationController extends Controller
{
    public function create(Request $request)
    {
        $org = $request->user()->organization;
        $ownsOrg = $org && $org->owner_id === $request->user()->id;

        return view('organizations.create', [
            'organization' => $ownsOrg ? $org : new Organization(['type' => 'komunitas']),
            'linkedOrg' => $org,
            'canEdit' => ! $org || ($ownsOrg && $org->status === 'rejected'),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $org = $user->organization;

        if ($org && ! ($org->owner_id === $user->id && $org->status === 'rejected')) {
            return redirect()->route('organizations.create')->with('error', 'Anda sudah terhubung dengan organisasi.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'type' => ['required', Rule::in(array_keys(Organization::TYPES))],
            'city' => ['required', 'string', 'max:80'],
            'description' => ['required', 'string', 'max:2000'],
            'contact' => ['required', 'string', 'max:150'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [], [
            'name' => 'nama organisasi', 'type' => 'jenis organisasi', 'city' => 'kota',
            'description' => 'deskripsi', 'contact' => 'kontak', 'logo' => 'logo',
        ]);

        $logo = $request->hasFile('logo') ? $request->file('logo')->store('organizations', 'public') : null;
        unset($data['logo']);
        $data += ['status' => 'pending', 'admin_note' => null, 'verified_at' => null, 'owner_id' => $user->id];

        if ($org) { // pengajuan ulang setelah ditolak
            if ($logo && $org->logo_path) {
                Storage::disk('public')->delete($org->logo_path);
            }
            $org->update($data + ($logo ? ['logo_path' => $logo] : []));
        } else {
            $org = Organization::create($data + ['logo_path' => $logo, 'slug' => Str::slug($data['name']).'-'.Str::lower(Str::random(4))]);
            $user->update(['organization_id' => $org->id]);
        }

        return redirect()->route('organizations.create')->with('success', 'Pendaftaran organisasi terkirim. Admin akan memverifikasi data Anda.');
    }
}
