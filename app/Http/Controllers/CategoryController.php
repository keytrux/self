<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        $categories = Category::query()
            ->withCount('recipes')
            ->orderBy('name')
            ->get();

        return view('categories.index', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless((bool) $request->user()?->is_admin, 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,name'],
        ]);

        Category::create([
            'name' => $validated['name'],
        ]);

        return redirect()
            ->route('categories.index')
            ->with('success', 'Категория добавлена.');
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        abort_unless((bool) $request->user()?->is_admin, 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('categories', 'name')->ignore($category->id)],
        ]);

        $category->update([
            'name' => $validated['name'],
        ]);

        return redirect()
            ->route('categories.index')
            ->with('success', 'Категория обновлена.');
    }

    public function destroy(Request $request, Category $category): RedirectResponse
    {
        abort_unless((bool) $request->user()?->is_admin, 403);

        $category->delete();

        return redirect()
            ->route('categories.index')
            ->with('success', 'Категория удалена.');
    }
}
