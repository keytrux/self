<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class IngredientResolver
{
    /**
     * @param  array<int, mixed>  $rows
     * @return array<int, array{product_id:int, amount:int|float, unit:string}>
     */
    public static function resolve(array $rows): array
    {
        $resolved = [];
        $errors = [];

        foreach ($rows as $index => $row) {
            $row = $row ?? [];

            $productId = $row['product_id'] ?? '';
            $isNewProduct = $productId === 'new';
            $amount = $row['amount'] ?? '';
            $newName = $row['new_name'] ?? '';

            if ($productId === '' && $amount === '' && $newName === '') {
                continue;
            }

            if ($isNewProduct) {
                $validator = Validator::make($row, [
                    'new_name' => ['required', 'string', 'max:255'],
                    'new_brand' => ['nullable', 'string', 'max:255'],
                    'new_calories' => ['required', 'numeric', 'min:0'],
                    'new_protein' => ['required', 'numeric', 'min:0'],
                    'new_fat' => ['required', 'numeric', 'min:0'],
                    'new_carbs' => ['required', 'numeric', 'min:0'],
                ], [
                    'new_name.required' => 'Укажите название нового продукта.',
                    'new_calories.required' => 'Укажите калорийность нового продукта.',
                    'new_calories.numeric' => 'Калорийность должна быть числом.',
                    'new_calories.min' => 'Калорийность не может быть отрицательной.',
                    'new_protein.required' => 'Укажите белки нового продукта.',
                    'new_protein.numeric' => 'Белки должны быть числом.',
                    'new_protein.min' => 'Белки не могут быть отрицательными.',
                    'new_fat.required' => 'Укажите жиры нового продукта.',
                    'new_fat.numeric' => 'Жиры должны быть числом.',
                    'new_fat.min' => 'Жиры не могут быть отрицательными.',
                    'new_carbs.required' => 'Укажите углеводы нового продукта.',
                    'new_carbs.numeric' => 'Углеводы должны быть числом.',
                    'new_carbs.min' => 'Углеводы не могут быть отрицательными.',
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $key => $messages) {
                        $errors["ingredients.{$index}.{$key}"] = $messages[0];
                    }
                    continue;
                }

                $product = Product::query()
                    ->where('name', $row['new_name'])
                    ->when(
                        !empty($row['new_brand']),
                        fn ($query) => $query->where('brand', $row['new_brand']),
                        fn ($query) => $query->whereNull('brand'),
                    )
                    ->first();

                if (! $product) {
                    $product = Product::create([
                        'user_id' => auth()->id(),
                        'name' => $row['new_name'],
                        'brand' => $row['new_brand'] ?? null,
                        'calories' => $row['new_calories'],
                        'protein' => $row['new_protein'],
                        'fat' => $row['new_fat'],
                        'carbs' => $row['new_carbs'],
                    ]);
                }

                $resolvedProductId = $product->id;
            } else {
                $resolvedProductId = (int) $productId;

                if (! Product::whereKey($resolvedProductId)->exists()) {
                    $errors["ingredients.{$index}.product_id"] = 'Выберите продукт из списка или создайте новый.';

                    continue;
                }
            }

            if (! is_numeric($amount) || (float) $amount <= 0) {
                $errors["ingredients.{$index}.amount"] = 'Количество должно быть больше нуля.';

                continue;
            }

            $resolved[] = [
                'product_id' => $resolvedProductId,
                'amount' => (float) $amount,
                'unit' => $row['unit'] ?? 'g',
            ];
        }

        if (! empty($errors)) {
            throw ValidationException::withMessages($errors);
        }

        return $resolved;
    }
}