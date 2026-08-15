<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Recipe;
use App\Support\IngredientResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RecipeController extends Controller
{
    public function index(Request $request): View
    {
        $statuses = [
            'to_cook' => ['label' => 'Хочу приготовить', 'emoji' => '📝'],
            'cooked' => ['label' => 'Приготовлено', 'emoji' => '🍳'],
            'liked' => ['label' => 'Понравилось', 'emoji' => '❤️'],
            'disliked' => ['label' => 'Не понравилось', 'emoji' => '👎'],
        ];

        $activeStatus = $request->string('status')->toString();
        $sort = $request->string('sort')->toString() === 'name' ? 'name' : 'date';

        $recipes = Recipe::query()
            ->with(['media', 'preparations.ingredients.product'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim($request->string('search'));
                $query->where('name', 'like', "%{$search}%");
            })
            ->when(
                $activeStatus !== '' && array_key_exists($activeStatus, $statuses),
                fn ($query) => $query->where('status', $activeStatus),
                fn ($query) => $query,
            )
            ->when(
                $sort === 'name',
                fn ($query) => $query->orderBy('name'),
                fn ($query) => $query->orderByDesc('created_at'),
            )
            ->get();

        $counts = Recipe::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('recipes.index', compact('recipes', 'statuses', 'counts', 'activeStatus', 'sort'));
    }

    public function create(): View
    {
        $products = Product::query()->orderBy('name')->get();

        return view('recipes.create', compact('products'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'instructions' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'status' => ['required', 'in:to_cook,cooked,liked,disliked'],
            'ingredients' => ['nullable', 'array'],
            'links' => ['nullable', 'array'],
            'links.*' => ['nullable', 'url', 'max:500'],
            'videos' => ['nullable', 'array'],
            'videos.*' => ['nullable', 'url', 'max:500'],
            'photos' => ['nullable', 'array'],
            'photos.*' => ['nullable', 'file', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:10240'],
        ]);

        $ingredients = IngredientResolver::resolve($request->input('ingredients', []));

        $recipe = Recipe::create([
            'user_id' => auth()->id(),
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'instructions' => $validated['instructions'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'status' => $validated['status'],
        ]);

        foreach ($ingredients as $ingredient) {
            $recipe->ingredients()->create([
                'product_id' => $ingredient['product_id'],
                'amount' => $ingredient['amount'],
                'unit' => $ingredient['unit'] ?? 'g',
            ]);
        }

        foreach (array_filter($validated['links'] ?? []) as $url) {
            $recipe->media()->create([
                'type' => 'link',
                'url' => $url,
            ]);
        }

        foreach (array_filter($validated['videos'] ?? []) as $url) {
            $recipe->media()->create([
                'type' => 'video',
                'url' => $url,
            ]);
        }

        foreach ($request->file('photos', []) as $photo) {
            $recipe->media()->create([
                'type' => 'photo',
                'path' => $photo->store('recipe-photos', 'public'),
            ]);
        }

        return redirect()
            ->route('recipes.show', $recipe)
            ->with('success', 'Рецепт создан.');
    }

    public function show(Recipe $recipe): View
    {
        $recipe->load([
            'ingredients.product',
            'preparations' => fn ($query) => $query->with('ingredients.product')->orderByDesc('prepared_at'),
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
}
