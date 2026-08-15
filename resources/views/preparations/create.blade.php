@extends('layouts.app')

@section('title', 'Новое приготовление')

@section('content')
    <div class="mb-6">
        <a href="{{ route('recipes.show', $recipe) }}" class="text-blue-600 hover:underline">← К рецепту</a>
        <h1 class="text-2xl font-bold mt-2">Новое приготовление</h1>
        <p class="text-gray-600">{{ $recipe->name }}</p>
    </div>

    <form method="POST" action="{{ route('preparations.store', $recipe) }}" class="bg-white rounded-lg shadow p-6 max-w-3xl">
        @csrf

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Дата *</label>
                <input type="date" name="prepared_at" value="{{ old('prepared_at', now()->format('Y-m-d')) }}" required
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-blue-500">
                @error('prepared_at') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Вес готового блюда (г) *</label>
                <input type="number" step="0.01" min="0.01" name="total_weight" value="{{ old('total_weight') }}" required
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-blue-500">
                @error('total_weight') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="mb-6">
            <div class="flex items-center justify-between mb-2">
                <label class="block text-sm font-medium text-gray-700">Фактические ингредиенты *</label>
                <button type="button" onclick="addIngredientRow()"
                        class="text-sm text-blue-600 hover:text-blue-800">+ Добавить ингредиент</button>
            </div>

            @php
                $ingredients = $recipe->ingredients
                    ->map(fn ($ing) => ['product_id' => $ing->product_id, 'amount' => $ing->amount, 'unit' => $ing->unit])
                    ->toArray();
            @endphp

            @include('partials.ingredient-rows', ['ingredients' => $ingredients, 'required' => true])
            @error('ingredients') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-1">Заметка</label>
            <textarea name="notes" rows="3"
                      class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-blue-500">{{ old('notes') }}</textarea>
            @error('notes') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="rounded-lg bg-blue-600 px-6 py-2 text-white hover:bg-blue-700">
                Сохранить приготовление
            </button>
            <a href="{{ route('recipes.show', $recipe) }}" class="text-gray-600 hover:text-gray-900">Отмена</a>
        </div>
    </form>
@endsection
