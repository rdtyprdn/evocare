<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index()
    {
        return view('admin.categories.index', ['categories' => Category::withCount('events')->orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(
            ['name' => ['required', 'string', 'max:60', 'unique:categories,name']],
            ['name.unique' => 'Kategori dengan nama ini sudah ada.'],
            ['name' => 'nama kategori'],
        );
        $base = Str::slug($data['name']) ?: 'kategori';
        $slug = $base;
        for ($i = 2; Category::where('slug', $slug)->exists(); $i++) {
            $slug = $base.'-'.$i;
        }
        Category::create(['name' => $data['name'], 'slug' => $slug]);

        return back()->with('success', 'Kategori ditambahkan.');
    }

    public function update(Request $request, Category $category)
    {
        $data = $request->validateWithBag('rename'.$category->id,
            ['name' => ['required', 'string', 'max:60', Rule::unique('categories', 'name')->ignore($category->id)]],
            ['name.unique' => 'Kategori dengan nama ini sudah ada.'],
            ['name' => 'nama kategori'],
        );
        $category->update(['name' => $data['name']]);

        return back()->with('success', 'Nama kategori diperbarui.');
    }

    public function destroy(Category $category)
    {
        $count = $category->events()->count();
        if ($count > 0) {
            return back()->with('error', "Kategori \"{$category->name}\" tidak dapat dihapus karena masih dipakai oleh {$count} kegiatan.");
        }
        $category->delete();

        return back()->with('success', 'Kategori dihapus.');
    }
}
