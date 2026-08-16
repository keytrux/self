<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $names = [
            'Завтраки',
            'Салаты',
            'Супы',
            'Вторые блюда',
            'Закуски',
            'Выпечка',
            'Десерты',
            'Соусы',
            'Напитки',
        ];

        foreach ($names as $name) {
            Category::firstOrCreate(['name' => $name]);
        }
    }
}
