<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index()
    {
        return view('admin.categories.index', ['categories' => Category::withCount('events')->orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:60', 'unique:categories,name']]);
        Category::create($data + ['slug' => Str::slug($data['name'])]);

        return back()->with('success', 'Kategori ditambahkan.');
    }

    public function destroy(Category $category)
    {
        if ($category->events()->exists()) {
            return back()->with('error', 'Kategori masih dipakai oleh kegiatan.');
        }
        $category->delete();

        return back()->with('success', 'Kategori dihapus.');
    }
}
