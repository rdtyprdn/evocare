<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OrganizationType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrganizationTypeController extends Controller
{
    public function index()
    {
        return view('admin.organization-types.index', [
            'types' => OrganizationType::withCount('organizations')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(
            ['name' => ['required', 'string', 'max:80', 'unique:organization_types,name']],
            ['name.unique' => 'Jenis organisasi dengan nama ini sudah ada.'],
            ['name' => 'nama jenis organisasi'],
        );
        OrganizationType::create(['name' => $data['name'], 'slug' => OrganizationType::uniqueSlug($data['name'])]);

        return back()->with('success', 'Jenis organisasi ditambahkan.');
    }

    public function update(Request $request, OrganizationType $organizationType)
    {
        $data = $request->validateWithBag('rename'.$organizationType->id,
            ['name' => ['required', 'string', 'max:80', Rule::unique('organization_types', 'name')->ignore($organizationType->id)]],
            ['name.unique' => 'Jenis organisasi dengan nama ini sudah ada.'],
            ['name' => 'nama jenis organisasi'],
        );
        $organizationType->update(['name' => $data['name']]);

        return back()->with('success', 'Nama jenis organisasi diperbarui.');
    }

    public function destroy(OrganizationType $organizationType)
    {
        $count = $organizationType->organizations()->count();
        if ($count > 0) {
            return back()->with('error', "Jenis organisasi \"{$organizationType->name}\" tidak dapat dihapus karena masih dipakai oleh {$count} organisasi.");
        }
        $organizationType->delete();

        return back()->with('success', 'Jenis organisasi dihapus.');
    }
}
