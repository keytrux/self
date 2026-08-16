@extends('layouts.app')

@section('title', 'Редактировать: ' . $product->name)

@section('content')
    <div class="mb-6">
        <a href="{{ route('products.show', $product) }}" class="text-blue-600 hover:underline">← Назад к продукту</a>
        <h1 class="text-2xl font-bold mt-2">Редактировать продукт</h1>
    </div>

    <form method="POST" action="{{ route('products.update', $product) }}" class="bg-white rounded-lg shadow p-6 max-w-2xl">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Название *</label>
                <input type="text" name="name" value="{{ old('name', $product->name) }}" required
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-blue-500">
                @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Бренд</label>
                <input type="text" name="brand" value="{{ old('brand', $product->brand) }}"
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-blue-500">
                @error('brand') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Штрихкод</label>
                <input type="text" name="barcode" value="{{ old('barcode', $product->barcode) }}"
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-blue-500">
                @error('barcode') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="mt-6">
            <p class="text-sm font-medium text-gray-700 mb-3">КБЖУ на 100 г *</p>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Калории (ккал)</label>
                    <input type="number" step="0.01" min="0" name="calories" value="{{ old('calories', $product->calories) }}" required
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-blue-500">
                    @error('calories') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Белки (г)</label>
                    <input type="number" step="0.01" min="0" name="protein" value="{{ old('protein', $product->protein) }}" required
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-blue-500">
                    @error('protein') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Жиры (г)</label>
                    <input type="number" step="0.01" min="0" name="fat" value="{{ old('fat', $product->fat) }}" required
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-blue-500">
                    @error('fat') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Углеводы (г)</label>
                    <input type="number" step="0.01" min="0" name="carbs" value="{{ old('carbs', $product->carbs) }}" required
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-blue-500">
                    @error('carbs') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Ссылка</label>
                <input type="url" name="source_url" value="{{ old('source_url', $product->source_url) }}"
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-blue-500">
                @error('source_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Заметка</label>
                <textarea name="notes" rows="3"
                          class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-blue-500">{{ old('notes', $product->notes) }}</textarea>
                @error('notes') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        @if (auth()->user()->is_admin)
            <div class="mt-6">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_public" value="1"
                           @checked(old('is_public', $product->is_public)) class="rounded">
                    <span class="text-sm font-medium text-gray-700">Общий продукт (доступен всем пользователям)</span>
                </label>
                @error('is_public') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        @endif

        <div class="mt-6 flex items-center gap-3">
            <button type="submit" class="rounded-lg bg-blue-600 px-6 py-2 text-white hover:bg-blue-700">
                Сохранить изменения
            </button>
            <a href="{{ route('products.show', $product) }}" class="text-gray-600 hover:text-gray-900">Отмена</a>
        </div>
    </form>
@endsection