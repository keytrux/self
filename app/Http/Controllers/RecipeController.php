<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Recipe;
use App\Support\IngredientResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RecipeController extends Controller
{
    public function index(Request $request): View
    {
        $activeStatus = match ($request->string('status')->toString()) {
            'all', 'cooked' => $request->string('status')->toString(),
            default => 'to_cook',
        };
        $sort = $request->string('sort')->toString() === 'name' ? 'name' : 'date';
        $categoryId = $request->integer('category') ?: null;
        $favoritesOnly = $request->boolean('favorites');

        $userId = auth()->id();

        $recipes = Recipe::query()
            ->visibleToUser($userId)
            ->with([
                'media',
                'category',
                'preparations' => fn ($query) => $query->visibleToUser($userId)->with('ingredients.product'),
            ])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim($request->string('search'));
                $query->where('name', 'like', "%{$search}%");
            })
            ->when($categoryId, fn ($query) => $query->where('category_id', $categoryId))
            ->when($favoritesOnly && $userId, fn ($query) => $query->whereHas('favoritedByUsers', fn ($q) => $q->whereKey($userId)))
            ->when(
                $activeStatus === 'cooked',
                fn ($query) => $query->has('preparations'),
                fn ($query) => $query->when($activeStatus === 'to_cook', fn ($q) => $q->whereDoesntHave('preparations')),
            )
            ->when(
                $sort === 'name',
                fn ($query) => $query->orderBy('name'),
                fn ($query) => $query->orderByDesc('created_at'),
            )
            ->paginate(12)
            ->withQueryString();

        $totalCount = Recipe::query()->visibleToUser($userId)->count();
        $cookedCount = Recipe::query()->visibleToUser($userId)->has('preparations')->count();

        $categories = Category::query()->orderBy('name')->get();

        return view(
            'recipes.index',
            compact('recipes', 'categories', 'activeStatus', 'sort', 'categoryId', 'favoritesOnly', 'totalCount', 'cookedCount')
        );
    }

    public function create(): View
    {
        $products = Product::query()->visibleToUser(auth()->id())->active()->orderBy('name')->get();
        $categories = Category::query()->orderBy('name')->get();

        return view('recipes.create', compact('products', 'categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules());

        $ingredients = IngredientResolver::resolve($request->input('ingredients', []));

        $isPublic = $request->user()->is_admin && $request->boolean('is_public');

        $recipe = Recipe::create([
            'user_id' => $request->user()->is_admin && $isPublic ? null : auth()->id(),
            'category_id' => $validated['category_id'] ?? null,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'instructions' => $validated['instructions'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'status' => 'to_cook',
            'is_public' => $isPublic,
        ]);

        $this->saveIngredients($recipe, $ingredients);
        $this->saveLinksAndVideos($recipe, $validated);
        $this->savePhotos($recipe, $request);

        return redirect()
            ->route('recipes.show', $recipe)
            ->with('success', 'Рецепт создан.');
    }

    public function show(Recipe $recipe): View
    {
        abort_unless($recipe->isVisibleTo(auth()->user()), 404);

        $recipe->load([
            'category',
            'ingredients.product',
            'preparations' => fn ($query) => $query
                ->visibleToUser(auth()->id())
                ->with('ingredients.product')
                ->orderByDesc('prepared_at'),
            'media',
        ]);

        $totals = [
            'calories' => $recipe->ingredients->sum(fn ($i) => $i->calories()),
            'protein' => $recipe->ingredients->sum(fn ($i) => $i->protein()),
            'fat' => $recipe->ingredients->sum(fn ($i) => $i->fat()),
            'carbs' => $recipe->ingredients->sum(fn ($i) => $i->carbs()),
        ];

        return view('recipes.show', compact('recipe', 'totals'));
    }

    public function edit(Recipe $recipe): View
    {
        abort_unless($recipe->isManagedBy(auth()->user()), 403);

        $recipe->load('ingredients.product');
        $products = Product::query()->visibleToUser(auth()->id())->active()->orderBy('name')->get();
        $categories = Category::query()->orderBy('name')->get();

        return view('recipes.edit', compact('recipe', 'products', 'categories'));
    }

    public function update(Request $request, Recipe $recipe): RedirectResponse
    {
        abort_unless($recipe->isManagedBy(auth()->user()), 403);

        $validated = $request->validate($this->rules());

        $ingredients = IngredientResolver::resolve($request->input('ingredients', []));

        $isPublic = $request->user()->is_admin && $request->boolean('is_public');

        $recipe->update([
            'category_id' => $validated['category_id'] ?? null,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'instructions' => $validated['instructions'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'is_public' => $isPublic,
        ]);

        $recipe->ingredients()->delete();
        $this->saveIngredients($recipe, $ingredients);

        $recipe->media()->whereIn('type', ['link', 'video'])->delete();
        $this->saveLinksAndVideos($recipe, $validated);

        foreach (array_values($validated['remove_photos'] ?? []) as $mediaId) {
            $photo = $recipe->media()->where('type', 'photo')->find($mediaId);
            if ($photo) {
                Storage::disk('public')->delete($photo->path);
                $photo->delete();
            }
        }

        $this->savePhotos($recipe, $request);

        return redirect()
            ->route('recipes.show', $recipe)
            ->with('success', 'Рецепт обновлён.');
    }

    public function destroy(Recipe $recipe): RedirectResponse
    {
        abort_unless($recipe->isManagedBy(auth()->user()), 403);

        foreach ($recipe->media()->where('type', 'photo')->get() as $photo) {
            Storage::disk('public')->delete($photo->path);
        }

        $recipe->delete();

        return redirect()
            ->route('recipes.index')
            ->with('success', 'Рецепт удалён.');
    }

    /**
     * Создать свою версию публичного рецепта (личная копия пользователя).
     */
    public function fork(Recipe $recipe): RedirectResponse
    {
        $user = auth()->user();
        abort_unless($user !== null && $recipe->isShared() && ! $recipe->isOwnedBy($user), 403);

        $copy = Recipe::create([
            'user_id' => $user->id,
            'category_id' => $recipe->category_id,
            'name' => $recipe->name,
            'description' => $recipe->description,
            'instructions' => $recipe->instructions,
            'notes' => $recipe->notes,
            'status' => 'to_cook',
        ]);

        foreach ($recipe->ingredients as $ingredient) {
            $copy->ingredients()->create([
                'product_id' => $ingredient->product_id,
                'amount' => $ingredient->amount,
                'unit' => $ingredient->unit,
                'sort_order' => $ingredient->sort_order,
            ]);
        }

        foreach ($recipe->links() as $link) {
            $copy->media()->create(['type' => 'link', 'url' => $link->url]);
        }

        foreach ($recipe->videos() as $video) {
            $copy->media()->create(['type' => 'video', 'url' => $video->url]);
        }

        foreach ($recipe->photos() as $photo) {
            $copyPath = null;

            if ($photo->path) {
                $copyPath = 'recipe-photos/'.Str::random(40).'.'.Str::afterLast($photo->path, '.');
                if (Storage::disk('public')->exists($photo->path)) {
                    Storage::disk('public')->copy($photo->path, $copyPath);
                }
            }

            $copy->media()->create(['type' => 'photo', 'path' => $copyPath]);
        }

        return redirect()
            ->route('recipes.show', $copy)
            ->with('success', 'Создана ваша версия рецепта.');
    }

    public function toggleFavorite(Request $request, Recipe $recipe): RedirectResponse
    {
        $user = auth()->user();
        abort_unless($user !== null && $recipe->isVisibleTo($user), 403);

        if ($recipe->isFavoritedBy($user)) {
            $recipe->favoritedByUsers()->detach($user->id);

            return back()->with('success', 'Рецепт удалён из избранного.');
        }

        $recipe->favoritedByUsers()->attach($user->id);

        return back()->with('success', 'Рецепт добавлен в избранное.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'description' => ['nullable', 'string'],
            'instructions' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'is_public' => ['nullable', 'boolean'],
            'ingredients' => ['nullable', 'array'],
            'links' => ['nullable', 'array'],
            'links.*' => ['nullable', 'url', 'max:500'],
            'videos' => ['nullable', 'array'],
            'videos.*' => ['nullable', 'url', 'max:500'],
            'photos' => ['nullable', 'array'],
            'photos.*' => ['nullable', 'file', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:10240'],
            'remove_photos' => ['nullable', 'array'],
            'remove_photos.*' => ['nullable', 'integer'],
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $ingredients
     */
    protected function saveIngredients(Recipe $recipe, array $ingredients): void
    {
        foreach (array_values($ingredients) as $order => $ingredient) {
            $recipe->ingredients()->create([
                'product_id' => $ingredient['product_id'],
                'amount' => $ingredient['amount'],
                'unit' => $ingredient['unit'] ?? 'g',
                'sort_order' => $order,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    protected function saveLinksAndVideos(Recipe $recipe, array $validated): void
    {
        foreach (array_filter($validated['links'] ?? []) as $url) {
            $recipe->media()->create(['type' => 'link', 'url' => $url]);
        }

        foreach (array_filter($validated['videos'] ?? []) as $url) {
            $recipe->media()->create(['type' => 'video', 'url' => $url]);
        }
    }

    protected function savePhotos(Recipe $recipe, Request $request): void
    {
        foreach ($request->file('photos', []) as $photo) {
            $recipe->media()->create([
                'type' => 'photo',
                'path' => $photo->store('recipe-photos', 'public'),
            ]);
        }
    }
}
