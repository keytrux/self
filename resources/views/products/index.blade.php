@extends('layouts.app')

@section('title', 'Продукты')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">Продукты</h1>
        <a href="{{ route('products.create') }}"
           class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">
            + Добавить продукт
        </a>
    </div>

    <form method="GET" action="{{ route('products.index') }}" class="mb-6">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Поиск по названию, бренду или штрихкоду..."
               class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-blue-500 focus:ring-blue-500">
    </form>

    @if ($products->isEmpty())
        <p class="text-gray-500">Продуктов пока нет. Добавьте первый!</p>
    @else
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Название</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Бренд</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ккал / 100 г</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Б</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ж</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">У</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Штрихкод</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($products as $product)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <a href="{{ route('products.show', $product) }}" class="text-blue-600 hover:underline">
                                    {{ $product->name }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ $product->brand ?: '—' }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format($product->calories, 1, '.', ' ') }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format($product->protein, 1, '.', ' ') }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format($product->fat, 1, '.', ' ') }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format($product->carbs, 1, '.', ' ') }}</td>
                            <td class="px-4 py-3 text-right text-gray-500">{{ $product->barcode ?: '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
