@php
    $ingredient = $ingredient ?? [];
    $isNew = ($ingredient['product_id'] ?? old("ingredients.{$index}.product_id")) === 'new';
    $productVal = old("ingredients.{$index}.product_id", $ingredient['product_id'] ?? null);
    $productName = old("ingredients.{$index}.product_name", $ingredient['product_name'] ?? '');
@endphp

<div class="ingredient-row grid grid-cols-12 gap-3 items-start">
    <div class="col-span-12 sm:col-span-6">
        <label class="block text-xs font-medium text-gray-500 mb-1">
            Продукт @if ($required)<span class="text-red-500">*</span>@endif
        </label>
        <div class="relative">
            <input type="text"
                   name="ingredients[{{ $index }}][product_name]"
                   value="{{ $productName }}"
                   placeholder="Поиск: название, бренд или штрихкод..."
                   autocomplete="off"
                   {{ $required ? 'required' : '' }}
                   class="ingredient-product-search w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-blue-500">
            <input type="hidden"
                   name="ingredients[{{ $index }}][product_id]"
                   value="{{ $isNew ? 'new' : $productVal }}"
                   class="ingredient-product-id">
            <div class="ingredient-product-suggestions absolute left-0 right-0 top-full z-10 mt-1 hidden max-h-56 overflow-y-auto rounded-lg border border-gray-300 bg-white shadow-lg"></div>
        </div>
        @error("ingredients.{$index}.product_id")
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="col-span-4 sm:col-span-2">
        <label class="block text-xs font-medium text-gray-500 mb-1">
            Кол-во @if ($required)<span class="text-red-500">*</span>@endif
        </label>
        <input type="number" step="0.01" min="0.01" name="ingredients[{{ $index }}][amount]"
               value="{{ old("ingredients.{$index}.amount", $ingredient['amount'] ?? null) }}"
               {{ $required ? 'required' : '' }}
               class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-blue-500">
        @error("ingredients.{$index}.amount")
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="col-span-3 sm:col-span-2">
        <label class="block text-xs font-medium text-gray-500 mb-1">Ед.</label>
        <select name="ingredients[{{ $index }}][unit]"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-blue-500">
            @foreach (['g' => 'г', 'ml' => 'мл', 'шт' => 'шт'] as $unitValue => $unitLabel)
                <option value="{{ $unitValue }}"
                    @selected(old("ingredients.{$index}.unit", $ingredient['unit'] ?? 'g') == $unitValue)>
                    {{ $unitLabel }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-span-1 sm:col-span-2">
        <label class="block text-xs font-medium text-gray-500 mb-1">&nbsp;</label>
        <button type="button" onclick="removeIngredientRow(this)"
                class="text-gray-400 hover:text-red-600 text-xl leading-none px-1">
            &times;
        </button>
    </div>

    <div class="col-span-12 new-product-fields {{ $isNew ? '' : 'hidden' }}">
        <div class="rounded-lg bg-gray-50 border border-gray-200 p-4 grid grid-cols-2 sm:grid-cols-3 gap-3">
            <div class="col-span-2 sm:col-span-1">
                <label class="block text-xs font-medium text-gray-500 mb-1">Название *</label>
                <input type="text" name="ingredients[{{ $index }}][new_name]"
                       value="{{ old("ingredients.{$index}.new_name", $ingredient['new_name'] ?? null) }}"
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-blue-500">
                @error("ingredients.{$index}.new_name")
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div class="col-span-2 sm:col-span-2">
                <label class="block text-xs font-medium text-gray-500 mb-1">Бренд</label>
                <input type="text" name="ingredients[{{ $index }}][new_brand]"
                       value="{{ old("ingredients.{$index}.new_brand", $ingredient['new_brand'] ?? null) }}"
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Ккал/100г *</label>
                <input type="number" step="0.01" min="0" name="ingredients[{{ $index }}][new_calories]"
                       value="{{ old("ingredients.{$index}.new_calories", $ingredient['new_calories'] ?? null) }}"
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-blue-500">
                @error("ingredients.{$index}.new_calories")
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Белки *</label>
                <input type="number" step="0.01" min="0" name="ingredients[{{ $index }}][new_protein]"
                       value="{{ old("ingredients.{$index}.new_protein", $ingredient['new_protein'] ?? null) }}"
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-blue-500">
                @error("ingredients.{$index}.new_protein")
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Жиры *</label>
                <input type="number" step="0.01" min="0" name="ingredients[{{ $index }}][new_fat]"
                       value="{{ old("ingredients.{$index}.new_fat", $ingredient['new_fat'] ?? null) }}"
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-blue-500">
                @error("ingredients.{$index}.new_fat")
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Углеводы *</label>
                <input type="number" step="0.01" min="0" name="ingredients[{{ $index }}][new_carbs]"
                       value="{{ old("ingredients.{$index}.new_carbs", $ingredient['new_carbs'] ?? null) }}"
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-blue-500">
                @error("ingredients.{$index}.new_carbs")
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>
</div>