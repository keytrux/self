<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $products = Product::query()
            ->visibleToUser(auth()->id())
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim($request->string('search'));

                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('brand', 'like', "%{$search}%")
                        ->orWhere('barcode', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->get();

        return view('products.index', compact('products'));
    }

    public function create(): View
    {
        return view('products.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules());

        $product = Product::create($validated + ['user_id' => auth()->id()]);

        return redirect()
            ->route('products.show', $product)
            ->with('success', 'Продукт добавлен.');
    }

    public function edit(Product $product): View
    {
        abort_unless($product->isOwnedBy(auth()->user()), 403);

        return view('products.edit', compact('product'));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        abort_unless($product->isOwnedBy(auth()->user()), 403);

        $validated = $request->validate($this->rules($product));

        $product->update($validated);

        return redirect()
            ->route('products.show', $product)
            ->with('success', 'Продукт обновлён.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        abort_unless($product->isOwnedBy(auth()->user()), 403);

        if ($product->recipeIngredients()->exists() || $product->preparationIngredients()->exists()) {
            throw ValidationException::withMessages([
                'product' => 'Нельзя удалить продукт: он используется в ингредиентах рецептов или приготовлений.',
            ]);
        }

        $product->delete();

        return redirect()
            ->route('products.index')
            ->with('success', 'Продукт удалён.');
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
