<?php

namespace App\Http\Controllers;

use App\Models\Preparation;
use App\Models\Product;
use App\Models\Recipe;
use App\Support\IngredientResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PreparationController extends Controller
{
    public function create(Recipe $recipe): View
    {
        abort_unless($recipe->isVisibleTo(auth()->user()), 404);

        $recipe->load('ingredients.product');
        $products = Product::query()->visibleToUser(auth()->id())->active()->orderBy('name')->get();

        return view('preparations.create', compact('recipe', 'products'));
    }

    public function store(Request $request, Recipe $recipe): RedirectResponse
    {
        abort_unless($recipe->isVisibleTo(auth()->user()), 404);

        $validated = $request->validate([
            'prepared_at' => ['required', 'date'],
            'total_weight' => ['required', 'numeric', 'gt:0'],
            'notes' => ['nullable', 'string'],
            'links' => ['nullable', 'array'],
            'links.*' => ['nullable', 'url', 'max:500'],
            'videos' => ['nullable', 'array'],
            'videos.*' => ['nullable', 'url', 'max:500'],
            'photos' => ['nullable', 'array'],
            'photos.*' => ['nullable', 'file', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:10240'],
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

        $this->saveMedia($preparation, $validated, $request);

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
            'media',
        ]);

        return view('preparations.show', compact('preparation'));
    }

    public function edit(Preparation $preparation): View
    {
        abort_unless($preparation->isOwnedBy(auth()->user()), 403);

        $preparation->load('ingredients.product', 'recipe', 'media');
        $products = Product::query()->visibleToUser(auth()->id())->active()->orderBy('name')->get();

        return view('preparations.edit', compact('preparation', 'products'));
    }

    public function update(Request $request, Preparation $preparation): RedirectResponse
    {
        abort_unless($preparation->isOwnedBy(auth()->user()), 403);

        $validated = $request->validate([
            'prepared_at' => ['required', 'date'],
            'total_weight' => ['required', 'numeric', 'gt:0'],
            'notes' => ['nullable', 'string'],
            'links' => ['nullable', 'array'],
            'links.*' => ['nullable', 'url', 'max:500'],
            'videos' => ['nullable', 'array'],
            'videos.*' => ['nullable', 'url', 'max:500'],
            'photos' => ['nullable', 'array'],
            'photos.*' => ['nullable', 'file', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:10240'],
            'remove_photos' => ['nullable', 'array'],
            'remove_photos.*' => ['nullable', 'integer'],
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

        $preparation->media()->whereIn('type', ['link', 'video'])->delete();

        foreach (array_values($validated['remove_photos'] ?? []) as $mediaId) {
            $photo = $preparation->media()->where('type', 'photo')->find($mediaId);
            if ($photo) {
                Storage::disk('public')->delete($photo->path);
                $photo->delete();
            }
        }

        $this->saveMedia($preparation, $validated, $request);

        return redirect()
            ->route('preparations.show', $preparation)
            ->with('success', 'Приготовление обновлено.');
    }

    public function destroy(Preparation $preparation): RedirectResponse
    {
        abort_unless($preparation->isOwnedBy(auth()->user()), 403);

        $recipe = $preparation->recipe;

        foreach ($preparation->media()->where('type', 'photo')->get() as $photo) {
            Storage::disk('public')->delete($photo->path);
        }

        $preparation->delete();

        return redirect()
            ->route('recipes.show', $recipe)
            ->with('success', 'Приготовление удалено.');
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    protected function saveMedia(Preparation $preparation, array $validated, Request $request): void
    {
        foreach (array_filter($validated['links'] ?? []) as $url) {
            $preparation->media()->create(['type' => 'link', 'url' => $url]);
        }

        foreach (array_filter($validated['videos'] ?? []) as $url) {
            $preparation->media()->create(['type' => 'video', 'url' => $url]);
        }

        foreach ($request->file('photos', []) as $photo) {
            $preparation->media()->create([
                'type' => 'photo',
                'path' => $photo->store('preparation-photos', 'public'),
            ]);
        }
    }
}
