@extends('layouts.app')

@section('title', 'Мои рецепты')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">Мои рецепты</h1>
        <a href="{{ route('recipes.create') }}"
           class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">
            + Добавить рецепт
        </a>
    </div>

    <form method="GET" action="{{ route('recipes.index') }}" class="mb-4">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Поиск по названию..."
               class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-blue-500 focus:ring-blue-500">
    </form>

    <div class="mb-6 flex flex-wrap items-center gap-2">
        <a href="{{ route('recipes.index') }}"
           class="rounded-full px-3 py-1 text-sm {{ $activeStatus === '' ? 'bg-gray-900 text-white' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50' }}">
            Все ({{ $recipes->count() }})
        </a>

        @foreach ($statuses as $key => $status)
            <a href="{{ route('recipes.index', array_merge(request()->only(['search', 'sort']), ['status' => $key])) }}"
               class="rounded-full px-3 py-1 text-sm {{ $activeStatus === $key ? 'bg-gray-900 text-white' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50' }}">
                {{ $status['emoji'] }} {{ $status['label'] }} ({{ $counts[$key] ?? 0 }})
            </a>
        @endforeach

        <div class="ml-auto flex items-center gap-2">
            <span class="text-sm text-gray-500">Сортировка:</span>
            <a href="{{ route('recipes.index', array_merge(request()->except(['sort']), ['sort' => 'date'])) }}"
               class="rounded-full px-3 py-1 text-sm {{ $sort === 'date' ? 'bg-gray-900 text-white' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50' }}">
                По дате
            </a>
            <a href="{{ route('recipes.index', array_merge(request()->except(['sort']), ['sort' => 'name'])) }}"
               class="rounded-full px-3 py-1 text-sm {{ $sort === 'name' ? 'bg-gray-900 text-white' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50' }}">
                По названию
            </a>
        </div>
    </div>

    @if ($recipes->isEmpty())
        <p class="text-gray-500">Рецептов пока нет. Добавьте первый!</p>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach ($recipes as $recipe)
                @php
                    $last = $recipe->lastPreparation();
                    $cover = $recipe->coverImage();
                @endphp
                <div class="bg-white rounded-lg shadow overflow-hidden flex flex-col">
                    <a href="{{ route('recipes.show', $recipe) }}" class="block">
                        @if ($cover)
                            <img src="{{ $cover->url ?: asset('storage/' . $cover->path) }}" alt="{{ $recipe->name }}"
                                 class="w-full h-40 object-cover">
                        @else
                            <div class="w-full h-40 bg-gray-100 flex items-center justify-center text-4xl text-gray-300">
                                🍽️
                            </div>
                        @endif
                    </a>

                    <div class="p-4 flex flex-col flex-1">
                        <h2 class="font-semibold text-gray-900">
                            <a href="{{ route('recipes.show', $recipe) }}" class="hover:underline">{{ $recipe->name }}</a>
                        </h2>

                        <div class="mt-2 flex items-center gap-2 text-sm">
                            <span class="rounded-full bg-gray-100 px-2 py-0.5 text-gray-700">
                                {{ $recipe->statusEmoji() }} {{ $recipe->statusLabel() }}
                            </span>
                        </div>

                        <div class="mt-auto pt-4 text-sm text-gray-500">
                            @if ($last)
                                <div class="flex justify-between">
                                    <span>Последнее приготовление: {{ $last->prepared_at->format('d.m.Y') }}</span>
                                    <span class="font-medium text-gray-700">
                                        {{ number_format($last->caloriesPer100(), 0, '.', ' ') }} ккал / 100 г
                                    </span>
                                </div>
                            @else
                                <span>Приготовлений ещё не было</span>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection
