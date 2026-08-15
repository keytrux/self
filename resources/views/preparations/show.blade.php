@extends('layouts.app')

@section('title', $preparation->recipe->name . ' — приготовление')

@section('content')
    <div class="mb-6">
        <a href="{{ route('recipes.show', $preparation->recipe) }}" class="text-blue-600 hover:underline">← К рецепту</a>
        <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold mt-2">{{ $preparation->recipe->name }}</h1>
            <p class="text-gray-600">Приготовление от {{ $preparation->prepared_at->format('d.m.Y') }}</p>
        </div>
        <div class="flex items-center gap-3">
            @if ($preparation->isOwnedBy(auth()->user()))
                <a href="{{ route('preparations.edit', $preparation) }}" class="text-blue-600 hover:underline">✏️ Редактировать</a>
                <form method="POST" action="{{ route('preparations.destroy', $preparation) }}" onsubmit="return confirm('Удалить это приготовление?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-red-600 hover:underline">🗑 Удалить</button>
                </form>
            @endif
        </div>
    </div>
    </div>

    <div class="bg-white rounded-lg shadow p-5 mb-6">
        <h2 class="text-sm font-semibold text-gray-500 mb-3">Ингредиенты</h2>
        <table class="min-w-full divide-y divide-gray-100">
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
                @foreach ($preparation->ingredients as $ingredient)
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
        </table>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow p-5">
            <h2 class="text-sm font-semibold text-gray-500 mb-3">Вес готового блюда</h2>
            <p class="text-2xl font-bold">{{ number_format($preparation->total_weight, 0, '.', ' ') }} г</p>
        </div>

        <div class="bg-white rounded-lg shadow p-5">
            <h2 class="text-sm font-semibold text-gray-500 mb-3">ИТОГО</h2>
            <div class="grid grid-cols-4 gap-2 text-center">
                <div>
                    <div class="text-xl font-bold text-blue-700">{{ number_format($preparation->calories(), 0, '.', ' ') }}</div>
                    <div class="text-xs text-gray-500 mt-1">ккал</div>
                </div>
                <div>
                    <div class="text-xl font-bold text-green-700">{{ number_format($preparation->protein(), 1, '.', ' ') }}</div>
                    <div class="text-xs text-gray-500 mt-1">белки</div>
                </div>
                <div>
                    <div class="text-xl font-bold text-amber-700">{{ number_format($preparation->fat(), 1, '.', ' ') }}</div>
                    <div class="text-xs text-gray-500 mt-1">жиры</div>
                </div>
                <div>
                    <div class="text-xl font-bold text-purple-700">{{ number_format($preparation->carbs(), 1, '.', ' ') }}</div>
                    <div class="text-xs text-gray-500 mt-1">углеводы</div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-5">
            <h2 class="text-sm font-semibold text-gray-500 mb-3">НА 100 Г</h2>
            <div class="grid grid-cols-4 gap-2 text-center">
                <div>
                    <div class="text-xl font-bold text-blue-700">{{ number_format($preparation->caloriesPer100(), 1, '.', ' ') }}</div>
                    <div class="text-xs text-gray-500 mt-1">ккал</div>
                </div>
                <div>
                    <div class="text-xl font-bold text-green-700">{{ number_format($preparation->proteinPer100(), 1, '.', ' ') }}</div>
                    <div class="text-xs text-gray-500 mt-1">белки</div>
                </div>
                <div>
                    <div class="text-xl font-bold text-amber-700">{{ number_format($preparation->fatPer100(), 1, '.', ' ') }}</div>
                    <div class="text-xs text-gray-500 mt-1">жиры</div>
                </div>
                <div>
                    <div class="text-xl font-bold text-purple-700">{{ number_format($preparation->carbsPer100(), 1, '.', ' ') }}</div>
                    <div class="text-xs text-gray-500 mt-1">углеводы</div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-5">
            <h2 class="text-sm font-semibold text-gray-500 mb-3">Рассчитать порцию</h2>
            <div class="flex items-center gap-2 mb-4">
                <input type="number" id="portion-weight" step="0.01" min="0" placeholder="Вес порции, г"
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-blue-500">
                <button type="button" onclick="calculatePortion()"
                        class="rounded-lg bg-gray-900 px-4 py-2 text-white hover:bg-gray-700">Рассчитать</button>
            </div>
            <div id="portion-result" class="hidden text-sm">
                <div class="grid grid-cols-4 gap-2 text-center">
                    <div>
                        <div id="portion-calories" class="font-bold text-blue-700"></div>
                        <div class="text-xs text-gray-500 mt-1">ккал</div>
                    </div>
                    <div>
                        <div id="portion-protein" class="font-bold text-green-700"></div>
                        <div class="text-xs text-gray-500 mt-1">белки</div>
                    </div>
                    <div>
                        <div id="portion-fat" class="font-bold text-amber-700"></div>
                        <div class="text-xs text-gray-500 mt-1">жиры</div>
                    </div>
                    <div>
                        <div id="portion-carbs" class="font-bold text-purple-700"></div>
                        <div class="text-xs text-gray-500 mt-1">углеводы</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($preparation->notes)
        <div class="bg-white rounded-lg shadow p-5">
            <h2 class="text-sm font-semibold text-gray-500 mb-2">Заметка</h2>
            <p class="text-gray-800 whitespace-pre-line">{{ $preparation->notes }}</p>
        </div>
    @endif

    <script>
        const per100 = {
            calories: {{ $preparation->caloriesPer100() }},
            protein: {{ $preparation->proteinPer100() }},
            fat: {{ $preparation->fatPer100() }},
            carbs: {{ $preparation->carbsPer100() }},
        };

        function calculatePortion() {
            const weight = parseFloat(document.getElementById('portion-weight').value);
            const result = document.getElementById('portion-result');

            if (!weight || weight <= 0) {
                return;
            }

            document.getElementById('portion-calories').textContent = (per100.calories * weight / 100).toFixed(1);
            document.getElementById('portion-protein').textContent = (per100.protein * weight / 100).toFixed(1);
            document.getElementById('portion-fat').textContent = (per100.fat * weight / 100).toFixed(1);
            document.getElementById('portion-carbs').textContent = (per100.carbs * weight / 100).toFixed(1);
            result.classList.remove('hidden');
        }
    </script>
@endsection
