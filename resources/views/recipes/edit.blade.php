@extends('layouts.app')

@section('title', 'Редактировать: ' . $recipe->name)

@section('content')
    <div class="mb-6">
        <a href="{{ route('recipes.show', $recipe) }}" class="text-blue-600 hover:underline">← К рецепту</a>
        <h1 class="text-2xl font-bold mt-2">Редактировать рецепт</h1>
    </div>

    <form method="POST" action="{{ route('recipes.update', $recipe) }}" enctype="multipart/form-data" class="bg-white rounded-lg shadow p-6 max-w-3xl">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Название *</label>
                <input type="text" name="name" value="{{ old('name', $recipe->name) }}" required
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-blue-500">
                @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Статус</label>
                <select name="status"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-blue-500">
                    <option value="to_cook" @selected(old('status', $recipe->status) === 'to_cook')>📝 Хочу приготовить</option>
                    <option value="cooked" @selected(old('status', $recipe->status) === 'cooked')>🍳 Приготовлено</option>
                    <option value="liked" @selected(old('status', $recipe->status) === 'liked')>❤️ Понравилось</option>
                    <option value="disliked" @selected(old('status', $recipe->status) === 'disliked')>👎 Не понравилось</option>
                </select>
                @error('status') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Описание</label>
                <textarea name="description" rows="2"
                          class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-blue-500">{{ old('description', $recipe->description) }}</textarea>
                @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="mt-6">
            <div class="flex items-center justify-between mb-2">
                <label class="block text-sm font-medium text-gray-700">Ингредиенты <span class="text-gray-400 font-normal">(необязательно)</span></label>
                <button type="button" onclick="addIngredientRow()"
                        class="text-sm text-blue-600 hover:text-blue-800">+ Добавить ингредиент</button>
            </div>
            @php
                $ingredients = $recipe->ingredients
                    ->map(fn ($ing) => ['product_id' => $ing->product_id, 'amount' => $ing->amount, 'unit' => $ing->unit])
                    ->toArray();
            @endphp
            @include('partials.ingredient-rows', ['ingredients' => $ingredients])
            @error('ingredients') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="mt-6">
            <label class="block text-sm font-medium text-gray-700 mb-1">Ссылки на источник</label>
            <div id="links-container" class="space-y-2">
                @foreach ($recipe->links() as $link)
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

        <div class="mt-6">
            <label class="block text-sm font-medium text-gray-700 mb-1">Видео (ссылки)</label>
            <div id="videos-container" class="space-y-2">
                @foreach ($recipe->videos() as $video)
                    <div class="video-row flex gap-2">
                        <input type="url" name="videos[]" value="{{ old('videos.' . $loop->index, $video->url) }}"
                               class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-blue-500">
                        <button type="button" onclick="removeMediaRow(this)"
                                class="text-gray-400 hover:text-red-600 text-xl leading-none px-1">&times;</button>
                    </div>
                @endforeach
            </div>
            <button type="button" onclick="addMediaRow('videos-container', 'videos[]')"
                    class="mt-2 text-sm text-blue-600 hover:text-blue-800">+ Добавить видео</button>
            @error('videos') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="mt-6">
            <label class="block text-sm font-medium text-gray-700 mb-1">Фотографии</label>

            @if ($recipe->photos()->isNotEmpty())
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mb-3">
                    @foreach ($recipe->photos() as $photo)
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

        <div class="mt-6">
            <label class="block text-sm font-medium text-gray-700 mb-1">Заметки</label>
            <textarea name="notes" rows="3"
                      class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-blue-500">{{ old('notes', $recipe->notes) }}</textarea>
            @error('notes') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="mt-6">
            <label class="block text-sm font-medium text-gray-700 mb-1">Текст приготовления</label>
            <textarea name="instructions" rows="6"
                      class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-blue-500">{{ old('instructions', $recipe->instructions) }}</textarea>
            @error('instructions') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="mt-6 flex items-center gap-3">
            <button type="submit" class="rounded-lg bg-blue-600 px-6 py-2 text-white hover:bg-blue-700">
                Сохранить изменения
            </button>
            <a href="{{ route('recipes.show', $recipe) }}" class="text-gray-600 hover:text-gray-900">Отмена</a>
        </div>
    </form>

    <script>
        function makeMediaRow(containerId, name) {
            const container = document.getElementById(containerId);
            const existing = container.querySelector('[class$="-row"]');
            if (existing) {
                return existing.cloneNode(true);
            }

            const row = document.createElement('div');
            row.className = 'link-row flex gap-2';
            row.innerHTML = `<input type="url" name="${name}" placeholder="https://..."
                class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-blue-500">
                <button type="button" onclick="removeMediaRow(this)"
                    class="text-gray-400 hover:text-red-600 text-xl leading-none px-1">&times;</button>`;
            return row;
        }

        function addMediaRow(containerId, name) {
            const container = document.getElementById(containerId);
            const clone = makeMediaRow(containerId, name);
            clone.querySelector('input').value = '';
            container.appendChild(clone);
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