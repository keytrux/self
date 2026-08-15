<?php

namespace App\Http\Controllers;

use App\Models\Preparation;
use App\Models\Product;
use App\Models\Recipe;
use App\Support\IngredientResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PreparationController extends Controller
{
    public function create(Recipe $recipe): View
    {
        abort_unless($recipe->isOwnedBy(auth()->user()), 403);

        $recipe->load('ingredients.product');
        $products = Product::query()->visibleToUser(auth()->id())->orderBy('name')->get();

        return view('preparations.create', compact('recipe', 'products'));
    }

    public function store(Request $request, Recipe $recipe): RedirectResponse
    {
        abort_unless($recipe->isOwnedBy(auth()->user()), 403);

        $validated = $request->validate([
            'prepared_at' => ['required', 'date'],
            'total_weight' => ['required', 'numeric', 'gt:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $ingredients = IngredientResolver::resolve($request->input('ingredients', []));

        if (count($ingredients) === 0) {
            throw ValidationException::withMessages([
                'ingredients' => 'Добавьте хотя бы один ингредиент.',
            ]);
        }

        $preparation = $recipe->preparations()->create([
            'user_id' => auth()->id(),
            'prepared_at' => $validated['prepared_at'],
            'total_weight' => $validated['total_weight'],
            'notes' => $validated['notes'] ?? null,
        ]);

        foreach ($ingredients as $ingredient) {
            $preparation->ingredients()->create($ingredient);
        }

        return redirect()
            ->route('preparations.show', $preparation)
            ->with('success', 'Приготовление сохранено.');
    }

    public function show(Preparation $preparation): View
    {
        abort_unless($preparation->isVisibleTo(auth()->user()), 404);

        $preparation->load([
            'recipe',
            'ingredients.product',
        ]);

        return view('preparations.show', compact('preparation'));
    }

    public function edit(Preparation $preparation): View
    {
        abort_unless($preparation->isOwnedBy(auth()->user()), 403);

        $preparation->load('ingredients.product', 'recipe');
        $products = Product::query()->visibleToUser(auth()->id())->orderBy('name')->get();

        return view('preparations.edit', compact('preparation', 'products'));
    }

    public function update(Request $request, Preparation $preparation): RedirectResponse
    {
        abort_unless($preparation->isOwnedBy(auth()->user()), 403);

        $validated = $request->validate([
            'prepared_at' => ['required', 'date'],
            'total_weight' => ['required', 'numeric', 'gt:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $ingredients = IngredientResolver::resolve($request->input('ingredients', []));

        if (count($ingredients) === 0) {
            throw ValidationException::withMessages([
                'ingredients' => 'Добавьте хотя бы один ингредиент.',
            ]);
        }

        $preparation->update([
            'prepared_at' => $validated['prepared_at'],
            'total_weight' => $validated['total_weight'],
            'notes' => $validated['notes'] ?? null,
        ]);

        $preparation->ingredients()->delete();
        foreach ($ingredients as $ingredient) {
            $preparation->ingredients()->create($ingredient);
        }

        return redirect()
            ->route('preparations.show', $preparation)
            ->with('success', 'Приготовление обновлено.');
    }

    public function destroy(Preparation $preparation): RedirectResponse
    {
        abort_unless($preparation->isOwnedBy(auth()->user()), 403);

        $recipe = $preparation->recipe;

        $preparation->delete();

        return redirect()
            ->route('recipes.show', $recipe)
            ->with('success', 'Приготовление удалено.');
    }
}
