@extends('layouts.app')

@section('title', 'Категории рецептов')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">Категории рецептов</h1>
        <a href="{{ route('recipes.index') }}" class="text-blue-600 hover:underline">← К рецептам</a>
    </div>

    @if (auth()->user()->is_admin)
        <form method="POST" action="{{ route('categories.store') }}" class="bg-white rounded-lg shadow p-5 mb-6 max-w-md flex gap-2">
            @csrf
            <input type="text" name="name" value="{{ old('name') }}" placeholder="Название новой категории"
                   class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-blue-500">
            <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">Добавить</button>
        </form>
        @error('name') <p class="mb-4 text-sm text-red-600">{{ $message }}</p> @enderror
    @endif

    @if ($categories->isEmpty())
        <p class="text-gray-500">Категорий пока нет.</p>
    @else
        <div class="bg-white rounded-lg shadow overflow-hidden max-w-md">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Название</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Рецептов</th>
                        @if (auth()->user()->is_admin)
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Действия</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($categories as $category)
                        <tr>
                            <td class="px-4 py-3">
                                @if (auth()->user()->is_admin)
                                    <form method="POST" action="{{ route('categories.update', $category) }}" class="flex gap-2">
                                        @csrf
                                        @method('PUT')
                                        <input type="text" name="name" value="{{ $category->name }}" required
                                               class="w-full rounded-lg border border-gray-300 px-3 py-1.5 focus:border-blue-500 focus:ring-blue-500">
                                        <button type="submit" class="text-green-600 hover:underline">Сохранить</button>
                                    </form>
                                @else
                                    {{ $category->name }}
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right text-gray-600">{{ $category->recipes_count }}</td>
                            @if (auth()->user()->is_admin)
                                <td class="px-4 py-3 text-right">
                                    <form method="POST" action="{{ route('categories.destroy', $category) }}"
                                          onsubmit="return confirm('Удалить категорию «{{ $category->name }}»? Рецепты сохранятся без категории.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline">Удалить</button>
                                    </form>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection