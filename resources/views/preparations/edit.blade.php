@extends('layouts.app')

@section('title', 'Редактировать приготовление')

@section('content')
    <div class="mb-6">
        <a href="{{ route('preparations.show', $preparation) }}" class="text-blue-600 hover:underline">← К приготовлению</a>
        <h1 class="text-2xl font-bold mt-2">Редактировать приготовление</h1>
        <p class="text-gray-600">{{ $preparation->recipe->name }}</p>
    </div>

    <form method="POST" action="{{ route('preparations.update', $preparation) }}" enctype="multipart/form-data" class="bg-white rounded-lg shadow p-6 max-w-3xl">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Дата *</label>
                <input type="date" name="prepared_at" value="{{ old('prepared_at', $preparation->prepared_at->format('Y-m-d')) }}" required
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-blue-500">
                @error('prepared_at') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Вес готового блюда (г) *</label>
                <input type="number" step="0.01" min="0.01" name="total_weight" value="{{ old('total_weight', $preparation->total_weight) }}" required
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
                $ingredients = $preparation->ingredients
                    ->map(fn ($ing) => [
                        'product_id' => $ing->product_id,
                        'product_name' => $ing->product?->name ?? '',
                        'amount' => $ing->amount,
                        'unit' => $ing->unit,
                    ])
                    ->toArray();
            @endphp

            @include('partials.ingredient-rows', ['ingredients' => $ingredients, 'required' => true])
            @error('ingredients') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-1">Заметка</label>
            <textarea name="notes" rows="3"
                      class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-blue-500">{{ old('notes', $preparation->notes) }}</textarea>
            @error('notes') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-1">Фотографии результата</label>

            @if ($preparation->photos()->isNotEmpty())
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mb-3">
                    @foreach ($preparation->photos() as $photo)
                        <div class="relative">
                            <img src="{{ asset('storage/' . $photo->path) }}" alt="" class="w-full h-32 object-cover rounded-lg">
                            <label class="absolute bottom-1 left-1 rounded bg-white/80 px-2 py-1 text-xs text-gray-800 cursor-pointer">
                                <input type="checkbox" name="remove_photos[]" value="{{ $photo->id }}" class="mr-1">
                                Удалить
                            </label>
                        </div>
                    @endforeach
                </div>
            @endif

            <input type="file" name="photos[]" multiple accept="image/*"
                   class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-blue-500">
            @error('photos') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            @error('photos.*') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-1">Ссылки</label>
            <div id="links-container" class="space-y-2">
                @foreach ($preparation->links() as $link)
                    <div class="link-row flex gap-2">
                        <input type="url" name="links[]" value="{{ old('links.' . $loop->index, $link->url) }}"
                               class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-blue-500">
                        <button type="button" onclick="removeMediaRow(this)"
                                class="text-gray-400 hover:text-red-600 text-xl leading-none px-1">&times;</button>
                    </div>
                @endforeach
            </div>
            <button type="button" onclick="addMediaRow('links-container', 'links[]')"
                    class="mt-2 text-sm text-blue-600 hover:text-blue-800">+ Добавить ссылку</button>
            @error('links') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="rounded-lg bg-blue-600 px-6 py-2 text-white hover:bg-blue-700">
                Сохранить изменения
            </button>
            <a href="{{ route('preparations.show', $preparation) }}" class="text-gray-600 hover:text-gray-900">Отмена</a>
        </div>
    </form>

    <script>
        function addMediaRow(containerId, name) {
            const container = document.getElementById(containerId);
            const existing = container.querySelector('[class$="-row"]');
            const row = existing
                ? existing.cloneNode(true)
                : (() => {
                    const row = document.createElement('div');
                    row.className = 'link-row flex gap-2';
                    row.innerHTML = `<input type="url" name="${name}" placeholder="https://..."
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-blue-500">
                        <button type="button" onclick="removeMediaRow(this)"
                            class="text-gray-400 hover:text-red-600 text-xl leading-none px-1">&times;</button>`;
                    return row;
                })();
            row.querySelector('input').value = '';
            container.appendChild(row);
        }

        function removeMediaRow(button) {
            const container = button.closest('[class$="-row"]').parentElement;
            const rows = container.querySelectorAll('[class$="-row"]');
            if (rows.length > 1) {
                button.closest('[class$="-row"]').remove();
            }
        }
    </script>
@endsection