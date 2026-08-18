@extends('layouts.app')

@section('title', $recipe->name)

@section('content')
    <div class="mb-6">
        <a href="{{ route('recipes.index') }}" class="text-blue-600 hover:underline">← К рецептам</a>
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-2xl font-bold">{{ $recipe->name }}</h1>
                @if ($recipe->isShared())
                    <span class="rounded-full bg-blue-50 px-3 py-1 text-sm text-blue-700">🌐 Публичный</span>
                @endif
                <span class="rounded-full bg-gray-100 px-3 py-1 text-sm text-gray-700">
                    {{ $recipe->isCooked() ? '✓ Приготовлено' : '📝 Хочу приготовить' }}
                </span>
                @if ($recipe->category)
                    <span class="rounded-full bg-gray-100 px-3 py-1 text-sm text-gray-700">{{ $recipe->category->name }}</span>
                @endif
            </div>
            <div class="flex flex-wrap items-center gap-2">
                @if (auth()->check() && ! $recipe->isOwnedBy(auth()->user()) && $recipe->isShared())
                    <form method="POST" action="{{ route('recipes.fork', $recipe) }}">
                        @csrf
                        <button type="submit"
                                class="inline-flex items-center whitespace-nowrap rounded-lg border border-gray-300 px-4 py-2 text-gray-700 hover:bg-gray-50">
                            📋 Создать свою версию
                        </button>
                    </form>
                @endif
                @if (auth()->check())
                    <form method="POST" action="{{ route('recipes.toggle-favorite', $recipe) }}">
                        @csrf
                        <button type="submit"
                                class="inline-flex items-center whitespace-nowrap rounded-lg border border-gray-300 px-4 py-2 {{ $recipe->isFavoritedBy(auth()->user()) ? 'text-yellow-600 bg-yellow-50' : 'text-gray-700 hover:bg-gray-50' }}">
                            {{ $recipe->isFavoritedBy(auth()->user()) ? '★ В избранном' : '☆ В избранное' }}
                        </button>
                    </form>
                @endif
                @if (auth()->check() && $recipe->isVisibleTo(auth()->user()))
                    <a href="{{ route('preparations.create', $recipe) }}"
                       class="inline-flex items-center whitespace-nowrap rounded-lg bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">
                        + Новое приготовление
                    </a>
                @endif
                @if ($recipe->isManagedBy(auth()->user()))
                    <div class="flex flex-wrap items-center gap-3">
                        <a href="{{ route('recipes.edit', $recipe) }}" class="text-blue-600 hover:underline">✏️ Редактировать</a>
                        <form method="POST" action="{{ route('recipes.destroy', $recipe) }}" onsubmit="return confirm('Удалить рецепт «{{ $recipe->name }}»? Это удалит и все его приготовления.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:underline">🗑 Удалить</button>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if ($recipe->description)
        <div class="bg-white rounded-lg shadow p-5 mb-6">
            <h2 class="text-sm font-semibold text-gray-500 mb-2">Описание</h2>
            <p class="text-gray-800 whitespace-pre-line">{{ $recipe->description }}</p>
        </div>
    @endif

    @php $gallery = $recipe->photos()->concat($recipe->videoFiles()); @endphp
    @if ($gallery->isNotEmpty())
        <div class="bg-white rounded-lg shadow p-5 mb-6">
            <h2 class="text-sm font-semibold text-gray-500 mb-3">Фото и видео</h2>
            <div class="carousel relative overflow-hidden rounded-lg" data-carousel>
                <div class="flex transition-transform duration-300" data-carousel-track>
                    @foreach ($gallery as $mediaItem)
                        <div class="w-full shrink-0" data-carousel-slide>
                            @if ($mediaItem->type === 'video' && $mediaItem->path)
                                <video src="{{ asset('storage/' . $mediaItem->path) }}" controls preload="metadata"
                                       playsinline class="w-full h-64 sm:h-96 object-contain bg-black">
                                    Ваш браузер не поддерживает видео.
                                </video>
                            @else
                                <img src="{{ asset('storage/' . $mediaItem->path) }}" alt="{{ $recipe->name }}"
                                     class="w-full h-64 sm:h-96 object-contain">
                            @endif
                        </div>
                    @endforeach
                </div>

                @if ($gallery->count() > 1)
                    <button type="button" data-carousel-prev
                            class="absolute left-2 top-1/2 -translate-y-1/2 rounded-full bg-white/80 p-2 text-gray-700 shadow hover:bg-white">
                        ‹
                    </button>
                    <button type="button" data-carousel-next
                            class="absolute right-2 top-1/2 -translate-y-1/2 rounded-full bg-white/80 p-2 text-gray-700 shadow hover:bg-white">
                        ›
                    </button>
                    <div class="absolute bottom-3 left-1/2 flex -translate-x-1/2 gap-1.5" data-carousel-dots>
                        @foreach ($gallery as $i => $mediaItem)
                            <button type="button" data-carousel-dot="{{ $i }}"
                                    class="h-2 w-2 rounded-full bg-white/60 {{ $i === 0 ? 'bg-white' : '' }}"></button>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    @endif

    @if ($recipe->links()->isNotEmpty())
        <div class="bg-white rounded-lg shadow p-5 mb-6">
            <h2 class="text-sm font-semibold text-gray-500 mb-2">Ссылки</h2>
            <ul class="space-y-1">
                @foreach ($recipe->links() as $link)
                    <li>
                        <a href="{{ $link->url }}" target="_blank" rel="noopener" class="text-blue-600 hover:underline">
                            🔗 {{ $link->url }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($recipe->videoLinks()->isNotEmpty())
        <div class="bg-white rounded-lg shadow p-5 mb-6">
            <h2 class="text-sm font-semibold text-gray-500 mb-2">Видео</h2>
            <ul class="space-y-1">
                @foreach ($recipe->videoLinks() as $video)
                    <li>
                        <a href="{{ $video->url }}" target="_blank" rel="noopener" class="text-blue-600 hover:underline">
                            🎬 {{ $video->url }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white rounded-lg shadow p-5 mb-6">
        <h2 class="text-sm font-semibold text-gray-500 mb-3">Ингредиенты рецепта</h2>

        @if ($recipe->ingredients->isEmpty())
            <p class="text-gray-500 text-sm">Ингредиенты не добавлены.</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-[560px] divide-y divide-gray-100 whitespace-nowrap">
                <thead>
                    <tr class="text-left text-xs text-gray-400 uppercase">
                        <th class="py-2">Продукт</th>
                        <th class="py-2 text-right">Кол-во</th>
                        <th class="py-2 text-right">Ккал</th>
                        <th class="py-2 text-right">Б</th>
                        <th class="py-2 text-right">Ж</th>
                        <th class="py-2 text-right">У</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($recipe->ingredients as $ingredient)
                        <tr>
                            <td class="py-2">
                                <a href="{{ route('products.show', $ingredient->product) }}" class="text-blue-600 hover:underline">
                                    {{ $ingredient->product->name }}
                                </a>
                            </td>
                            <td class="py-2 text-right text-gray-600">{{ number_format($ingredient->amount, 0, '.', ' ') }} {{ $ingredient->unit }}</td>
                            <td class="py-2 text-right">{{ number_format($ingredient->calories(), 0, '.', ' ') }}</td>
                            <td class="py-2 text-right">{{ number_format($ingredient->protein(), 1, '.', ' ') }}</td>
                            <td class="py-2 text-right">{{ number_format($ingredient->fat(), 1, '.', ' ') }}</td>
                            <td class="py-2 text-right">{{ number_format($ingredient->carbs(), 1, '.', ' ') }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="border-t-2 border-gray-200 font-medium">
                    <tr>
                        <td class="py-3">ИТОГО</td>
                        <td class="py-3"></td>
                        <td class="py-3 text-right">{{ number_format($totals['calories'], 0, '.', ' ') }} ккал</td>
                        <td class="py-3 text-right">{{ number_format($totals['protein'], 1, '.', ' ') }} г</td>
                        <td class="py-3 text-right">{{ number_format($totals['fat'], 1, '.', ' ') }} г</td>
                        <td class="py-3 text-right">{{ number_format($totals['carbs'], 1, '.', ' ') }} г</td>
                    </tr>
                </tfoot>
                </table>
            </div>
        @endif
    </div>

    @if ($recipe->instructions)
        <div class="bg-white rounded-lg shadow p-5 mb-6">
            <h2 class="text-sm font-semibold text-gray-500 mb-2">Текст приготовления</h2>
            <div class="text-gray-800 whitespace-pre-line">{{ $recipe->instructions }}</div>
        </div>
    @endif

    @if ($recipe->notes)
        <div class="bg-white rounded-lg shadow p-5 mb-6">
            <h2 class="text-sm font-semibold text-gray-500 mb-2">Заметки</h2>
            <p class="text-gray-800 whitespace-pre-line">{{ $recipe->notes }}</p>
        </div>
    @endif

    <div class="bg-white rounded-lg shadow p-5">
        <h2 class="text-sm font-semibold text-gray-500 mb-3">История приготовлений</h2>

        @if ($recipe->preparations->isEmpty())
            <p class="text-gray-500 text-sm">Приготовлений ещё не было.</p>
        @else
            <div class="space-y-2">
                @foreach ($recipe->preparations as $preparation)
                    <a href="{{ route('preparations.show', $preparation) }}"
                       class="flex items-center justify-between rounded-lg border border-gray-100 px-4 py-3 hover:bg-gray-50">
                        <span class="font-medium text-gray-800">{{ $preparation->prepared_at->format('d.m.Y') }}</span>
                        <span class="text-gray-600">{{ number_format($preparation->caloriesPer100(), 0, '.', ' ') }} ккал / 100 г</span>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
@endsection
