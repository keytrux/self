@extends('layouts.app')

@section('title', 'Добавить рецепт')

@section('content')
    <div class="mb-6">
        <a href="{{ route('recipes.index') }}" class="text-blue-600 hover:underline">← Назад к рецептам</a>
        <h1 class="text-2xl font-bold mt-2">Добавить рецепт</h1>
    </div>

    <form method="POST" action="{{ route('recipes.store') }}" enctype="multipart/form-data" class="bg-white rounded-lg shadow p-6 max-w-3xl">
        @csrf

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Название *</label>
                <input type="text" name="name" value="{{ old('name') }}" required
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-blue-500">
                @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Категория</label>
                <select name="category_id"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Без категории</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
                @error('category_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            @if (auth()->user()->is_admin)
                <div class="sm:col-span-2">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_public" value="1" @checked(old('is_public')) class="rounded">
                        <span class="text-sm font-medium text-gray-700">Публичный рецепт (виден всем пользователям)</span>
                    </label>
                    @error('is_public') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            @endif

            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Описание</label>
                <textarea name="description" rows="2"
                          class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-blue-500">{{ old('description') }}</textarea>
                @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="mt-6">
            <div class="flex items-center justify-between mb-2">
                <label class="block text-sm font-medium text-gray-700">Ингредиенты <span class="text-gray-400 font-normal">(необязательно)</span></label>
                <button type="button" onclick="addIngredientRow()"
                        class="text-sm text-blue-600 hover:text-blue-800">+ Добавить ингредиент</button>
            </div>
            @include('partials.ingredient-rows')
            @error('ingredients') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="mt-6">
            <label class="block text-sm font-medium text-gray-700 mb-1">Ссылки на источник</label>
            <div id="links-container" class="space-y-2">
                <div class="link-row flex gap-2">
                    <input type="url" name="links[]" placeholder="https://..."
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-blue-500">
                    <button type="button" onclick="removeMediaRow(this)"
                            class="text-gray-400 hover:text-red-600 text-xl leading-none px-1">&times;</button>
                </div>
            </div>
            <button type="button" onclick="addMediaRow('links-container', 'links[]')"
                    class="mt-2 text-sm text-blue-600 hover:text-blue-800">+ Добавить ссылку</button>
            @error('links') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="mt-6">
            <label class="block text-sm font-medium text-gray-700 mb-1">Видео (ссылки)</label>
            <div id="videos-container" class="space-y-2">
                <div class="video-row flex gap-2">
                    <input type="url" name="videos[]" placeholder="https://..."
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-blue-500">
                    <button type="button" onclick="removeMediaRow(this)"
                            class="text-gray-400 hover:text-red-600 text-xl leading-none px-1">&times;</button>
                </div>
            </div>
            <button type="button" onclick="addMediaRow('videos-container', 'videos[]')"
                    class="mt-2 text-sm text-blue-600 hover:text-blue-800">+ Добавить видео</button>
            @error('videos') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="mt-6">
            <label class="block text-sm font-medium text-gray-700 mb-1">Видео (файлы)</label>
            <input type="file" name="video_files[]" multiple accept="video/*"
                   class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-blue-500">
            @error('video_files') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            @error('video_files.*') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="mt-6">
            <label class="block text-sm font-medium text-gray-700 mb-1">Фотографии</label>
            <input type="file" name="photos[]" multiple accept="image/*"
                   class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-blue-500">
            @error('photos') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            @error('photos.*') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="mt-6">
            <label class="block text-sm font-medium text-gray-700 mb-1">Заметки</label>
            <textarea name="notes" rows="3"
                      class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-blue-500">{{ old('notes') }}</textarea>
            @error('notes') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="mt-6">
            <label class="block text-sm font-medium text-gray-700 mb-1">Текст приготовления</label>
            <textarea name="instructions" rows="6"
                      class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-blue-500">{{ old('instructions') }}</textarea>
            @error('instructions') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="mt-6 flex items-center gap-3">
            <button type="submit" class="rounded-lg bg-blue-600 px-6 py-2 text-white hover:bg-blue-700">
                Сохранить рецепт
            </button>
            <a href="{{ route('recipes.index') }}" class="text-gray-600 hover:text-gray-900">Отмена</a>
        </div>
    </form>

    <script>
        function addMediaRow(containerId, name) {
            const container = document.getElementById(containerId);
            const row = container.querySelector('[class$="-row"]');
            const clone = row.cloneNode(true);
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
