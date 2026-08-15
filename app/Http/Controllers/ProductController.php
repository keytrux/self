<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $products = Product::query()
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
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:255'],
            'barcode' => ['nullable', 'string', 'max:255', 'unique:products,barcode'],
            'calories' => ['required', 'numeric', 'min:0'],
            'protein' => ['required', 'numeric', 'min:0'],
            'fat' => ['required', 'numeric', 'min:0'],
            'carbs' => ['required', 'numeric', 'min:0'],
            'source_url' => ['nullable', 'url', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $product = Product::create($validated);

        return redirect()
            ->route('products.show', $product)
            ->with('success', 'Продукт добавлен.');
    }

    public function show(Product $product): View
    {
        return view('products.show', compact('product'));
    }
}
