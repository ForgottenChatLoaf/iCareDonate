<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::withCount(['donations', 'requests'])
            ->orderBy('sort_order')
            ->get();

        return view('admin.categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:categories,name',
            'description' => 'nullable|string|max:1000',
            'icon' => 'nullable|string|max:50',
            'sort_order' => 'nullable|integer',
        ]);

        $category = Category::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
            'icon' => $validated['icon'] ?? 'package',
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => true,
        ]);

        AuditLog::log('CATEGORY_CREATED', 'category', $category->id, "Admin created category '{$category->name}'.");

        return back()->with('success', "Category '{$category->name}' created.");
    }

    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:categories,name,' . $category->id,
            'description' => 'nullable|string|max:1000',
            'sort_order' => 'nullable|integer',
            'is_active' => 'boolean',
        ]);

        $category->update([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
            'sort_order' => $validated['sort_order'] ?? $category->sort_order,
            'is_active' => $request->has('is_active'),
        ]);

        AuditLog::log('CATEGORY_UPDATED', 'category', $category->id, "Admin updated category '{$category->name}'.");

        return back()->with('success', "Category updated.");
    }

    public function destroy($id)
    {
        $category = Category::withCount('donations')->findOrFail($id);

        if ($category->donations_count > 0) {
            return back()->with('error', "Cannot delete category '{$category->name}' because it has {$category->donations_count} associated donation items.");
        }

        $name = $category->name;
        $category->delete();

        AuditLog::log('CATEGORY_DELETED', 'category', $id, "Admin deleted category '{$name}'.");

        return back()->with('success', "Category '{$name}' deleted.");
    }
}
