<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductController extends Controller
{
    /**
     * Динамический поиск продуктов для выбора ингредиента (обработчик автокомплита).
     * Только личные продукты пользователя + общие/публичные.
     */
    public function search(Request $request): JsonResponse
    {
        $query = trim((string) $request->string('q'));

        $products = Product::query()
            ->visibleToUser(auth()->id())
            ->active()
            ->when($query !== '', function ($builder) use ($query) {
                $builder->where(function ($builder) use ($query) {
                    $builder->where('name', 'like', "%{$query}%")
                        ->orWhere('brand', 'like', "%{$query}%")
                        ->orWhere('barcode', 'like', "%{$query}%");
                });
            })
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'brand', 'calories']);

        return response()->json($products);
    }

    public function index(Request $request): View
    {
        $products = Product::query()
            ->visibleToUser(auth()->id())
            ->when($request->boolean('include_archived') === false, fn ($query) => $query->active())
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim($request->string('search'));

                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('brand', 'like', "%{$search}%")
                        ->orWhere('barcode', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('products.index', compact('products'));
    }

    public function create(): View
    {
        return view('products.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules());

        $product = Product::create(
            $validated
            + ['user_id' => auth()->id()]
            + ['is_public' => $request->user()->is_admin && $request->boolean('is_public')]
        );

        return redirect()
            ->route('products.show', $product)
            ->with('success', 'Продукт добавлен.');
    }

    public function edit(Product $product): View
    {
        abort_unless($product->isManagedBy(auth()->user()), 403);

        return view('products.edit', compact('product'));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        abort_unless($product->isManagedBy(auth()->user()), 403);

        $validated = $request->validate($this->rules($product));

        $product->update(
            $validated
            + ['is_public' => $request->user()->is_admin && $request->boolean('is_public')]
        );

        return redirect()
            ->route('products.show', $product)
            ->with('success', 'Продукт обновлён.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        abort_unless($product->isManagedBy(auth()->user()), 403);

        if ($product->recipeIngredients()->exists() || $product->preparationIngredients()->exists()) {
            // По ТЗ: используемый продукт нельзя удалить — архивируем.
            $product->update(['is_active' => false]);

            return redirect()
                ->route('products.show', $product)
                ->with('success', 'Продукт используется в рецептах и заархивирован.');
        }

        $product->delete();

        return redirect()
            ->route('products.index')
            ->with('success', 'Продукт удалён.');
    }

    /**
     * Архивация: продукт, используемый в рецептах/приготовлениях,
     * не удаляется, а помечается is_active = false.
     */
    public function archive(Product $product): RedirectResponse
    {
        abort_unless($product->isManagedBy(auth()->user()), 403);

        $product->update(['is_active' => false]);

        return redirect()
            ->route('products.show', $product)
            ->with('success', 'Продукт архивирован.');
    }

    public function restore(Product $product): RedirectResponse
    {
        abort_unless($product->isManagedBy(auth()->user()), 403);

        $product->update(['is_active' => true]);

        return redirect()
            ->route('products.show', $product)
            ->with('success', 'Продукт восстановлен.');
    }

    public function show(Product $product): View
    {
        abort_unless($product->isVisibleTo(auth()->user()), 404);

        return view('products.show', compact('product'));
    }

    /**
     * @return array<string, string|array<int, string>>
     */
    protected function rules(?Product $product = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:255'],
            'barcode' => ['nullable', 'string', 'max:255', Rule::unique('products', 'barcode')->ignore($product?->id)],
            'calories' => ['required', 'numeric', 'min:0'],
            'protein' => ['required', 'numeric', 'min:0'],
            'fat' => ['required', 'numeric', 'min:0'],
            'carbs' => ['required', 'numeric', 'min:0'],
            'source_url' => ['nullable', 'url', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
