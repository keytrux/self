<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IngredientSearchInputTest extends TestCase
{
    use RefreshDatabase;

    public function test_recipe_and_preparation_forms_render_search_input(): void
    {
        $user = User::factory()->create();
        $product = Product::create([
            'user_id' => $user->id, 'name' => 'Сливки 20%', 'brand' => null,
            'calories' => 120, 'protein' => 3, 'fat' => 9, 'carbs' => 4,
        ]);
        $recipe = Recipe::create(['user_id' => $user->id, 'name' => 'Тест']);
        $recipe->ingredients()->create([
            'product_id' => $product->id, 'amount' => 100, 'unit' => 'g', 'sort_order' => 0,
        ]);

        $this->actingAs($user)
            ->get(route('recipes.create'))
            ->assertOk()
            ->assertSee('ingredient-product-search', false)
            ->assertSee('initIngredientProductSearch', false);

        $this->actingAs($user)
            ->get(route('preparations.create', $recipe))
            ->assertOk()
            ->assertSee('ingredient-product-search', false)
            ->assertSee('initIngredientProductSearch', false);

        $this->actingAs($user)
            ->get(route('recipes.edit', $recipe))
            ->assertOk()
            ->assertSee('ingredient-product-search', false)
            ->assertSee('Сливки 20%')
            ->assertSee('value="1"', false);
    }
}
