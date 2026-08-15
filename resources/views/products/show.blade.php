@extends('layouts.app')

@section('title', $product->name)

@section('content')
    <div class="mb-6">
        <a href="{{ route('products.index') }}" class="text-blue-600 hover:underline">← К списку продуктов</a>
        <div class="flex items-center justify-between mt-2">
            <h1 class="text-2xl font-bold">{{ $product->name }}</h1>
            <div class="flex items-center gap-3">
                <a href="{{ route('products.create') }}" class="text-blue-600 hover:underline">+ Добавить продукт</a>
                @if ($product->isOwnedBy(auth()->user()))
                    <a href="{{ route('products.edit', $product) }}" class="text-blue-600 hover:underline">✏️ Редактировать</a>
                    <form method="POST" action="{{ route('products.destroy', $product) }}" onsubmit="return confirm('Удалить продукт «{{ $product->name }}»?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-600 hover:underline">🗑 Удалить</button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6 max-w-2xl">
        @if ($errors->has('product'))
            <p class="mb-4 rounded-lg bg-red-50 text-red-700 px-4 py-3 text-sm">⚠️ {{ $errors->first('product') }}</p>
        @endif

        @if ($product->brand)
            <p class="text-gray-600 mb-4"><strong>Бренд:</strong> {{ $product->brand }}</p>
        @endif

        <div class="grid grid-cols-4 gap-4 text-center mb-6">
            <div class="bg-blue-50 rounded-lg py-4">
                <div class="text-xl font-bold text-blue-700">{{ number_format($product->calories, 1, '.', ' ') }}</div>
                <div class="text-xs text-gray-500 mt-1">ккал</div>
            </div>
            <div class="bg-green-50 rounded-lg py-4">
                <div class="text-xl font-bold text-green-700">{{ number_format($product->protein, 1, '.', ' ') }}</div>
                <div class="text-xs text-gray-500 mt-1">белки</div>
            </div>
            <div class="bg-amber-50 rounded-lg py-4">
                <div class="text-xl font-bold text-amber-700">{{ number_format($product->fat, 1, '.', ' ') }}</div>
                <div class="text-xs text-gray-500 mt-1">жиры</div>
            </div>
            <div class="bg-purple-50 rounded-lg py-4">
                <div class="text-xl font-bold text-purple-700">{{ number_format($product->carbs, 1, '.', ' ') }}</div>
                <div class="text-xs text-gray-500 mt-1">углеводы</div>
            </div>
        </div>

        <dl class="text-sm">
            @if ($product->barcode)
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <dt class="text-gray-500">Штрихкод</dt>
                    <dd>{{ $product->barcode }}</dd>
                </div>
            @endif
            @if ($product->source_url)
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <dt class="text-gray-500">Источник</dt>
                    <dd><a href="{{ $product->source_url }}" target="_blank" class="text-blue-600 hover:underline">ссылка</a></dd>
                </div>
            @endif
            @if ($product->notes)
                <div class="py-2">
                    <dt class="text-gray-500 mb-1">Заметка</dt>
                    <dd class="text-gray-700">{{ $product->notes }}</dd>
                </div>
            @endif
        </dl>
    </div>
@endsection
