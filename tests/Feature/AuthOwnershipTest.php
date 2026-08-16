<?php

namespace Tests\Feature;

use App\Models\Preparation;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthOwnershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_for_protected_routes(): void
    {
        $this->get('/products/create')->assertRedirect('/login');
        $this->get('/recipes/create')->assertRedirect('/login');
        $this->get('/recipes/1/edit')->assertRedirect('/login');
        $this->post('/products')->assertRedirect('/login');
        $this->post('/recipes')->assertRedirect('/login');
    }

    public function test_registration_creates_user_and_logs_in(): void
    {
        $response = $this->post('/register', [
            'name' => 'Виктория',
            'email' => 'vika@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertRedirect('/');
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'vika@example.com']);
    }

    public function test_first_user_claims_shared_data(): void
    {
        $product = Product::create([
            'user_id' => null,
            'name' => 'Общий продукт',
            'brand' => null,
            'calories' => 100,
            'protein' => 10,
            'fat' => 5,
            'carbs' => 20,
        ]);
        $recipe = Recipe::create([
            'user_id' => null,
            'name' => 'Общий рецепт',
            'status' => 'to_cook',
        ]);

        $this->post('/register', [
            'name' => 'Виктория',
            'email' => 'vika@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $this->assertAuthenticated();
        $this->assertSame(auth()->id(), $product->fresh()->user_id);
        $this->assertSame(auth()->id(), $recipe->fresh()->user_id);
    }

    public function test_login_with_valid_credentials(): void
    {
        $user = User::factory()->create(['password' => 'secret123']);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'secret123',
        ])->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_with_invalid_credentials_fails(): void
    {
        User::factory()->create(['password' => 'secret123']);

        $this->post('/login', [
            'email' => 'wrong@example.com',
            'password' => 'wrongpass',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_logout_logs_out_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);
        $this->assertAuthenticatedAs($user);

        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_user_sees_only_own_and_shared_products(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $own = Product::create([
            'user_id' => $user->id,
            'name' => 'Мой продукт',
            'brand' => null,
            'calories' => 1,
            'protein' => 1,
            'fat' => 1,
            'carbs' => 1,
        ]);
        $shared = Product::create([
            'user_id' => null,
            'name' => 'Общий продукт',
            'brand' => null,
            'calories' => 1,
            'protein' => 1,
            'fat' => 1,
            'carbs' => 1,
        ]);
        $foreign = Product::create([
            'user_id' => $other->id,
            'name' => 'Чужой продукт',
            'brand' => null,
            'calories' => 1,
            'protein' => 1,
            'fat' => 1,
            'carbs' => 1,
        ]);

        $this->actingAs($user)
            ->get('/products')
            ->assertSee('Мой продукт')
            ->assertSee('Общий продукт')
            ->assertDontSee('Чужой продукт');
    }

    public function test_guest_sees_only_shared_products(): void
    {
        $user = User::factory()->create();

        Product::create([
            'user_id' => $user->id,
            'name' => 'Приватный',
            'brand' => null,
            'calories' => 1,
            'protein' => 1,
            'fat' => 1,
            'carbs' => 1,
        ]);
        Product::create([
            'user_id' => null,
            'name' => 'Общий',
            'brand' => null,
            'calories' => 1,
            'protein' => 1,
            'fat' => 1,
            'carbs' => 1,
        ]);

        $this->get('/products')
            ->assertSee('Общий')
            ->assertDontSee('Приватный');
    }

    public function test_user_cannot_view_foreign_product_show(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $product = Product::create([
            'user_id' => $other->id,
            'name' => 'Чужой',
            'brand' => null,
            'calories' => 1,
            'protein' => 1,
            'fat' => 1,
            'carbs' => 1,
        ]);

        $this->actingAs($user)->get("/products/{$product->id}")->assertNotFound();
        $this->get("/products/{$product->id}")->assertNotFound();
    }

    public function test_user_sees_own_and_shared_product_show(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $own = Product::create([
            'user_id' => $user->id,
            'name' => 'Мой',
            'brand' => null,
            'calories' => 1,
            'protein' => 1,
            'fat' => 1,
            'carbs' => 1,
        ]);
        $shared = Product::create([
            'user_id' => null,
            'name' => 'Общий',
            'brand' => null,
            'calories' => 1,
            'protein' => 1,
            'fat' => 1,
            'carbs' => 1,
        ]);

        $this->actingAs($user)->get("/products/{$own->id}")->assertOk();
        $this->actingAs($user)->get("/products/{$shared->id}")->assertOk();
        $this->get("/products/{$shared->id}")->assertOk();
    }

    public function test_user_cannot_edit_foreign_or_shared_product(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $foreign = Product::create([
            'user_id' => $other->id,
            'name' => 'Чужой',
            'brand' => null,
            'calories' => 1,
            'protein' => 1,
            'fat' => 1,
            'carbs' => 1,
        ]);
        $shared = Product::create([
            'user_id' => null,
            'name' => 'Общий',
            'brand' => null,
            'calories' => 1,
            'protein' => 1,
            'fat' => 1,
            'carbs' => 1,
        ]);

        $this->actingAs($user)->get("/products/{$foreign->id}/edit")->assertForbidden();
        $this->actingAs($user)->get("/products/{$shared->id}/edit")->assertForbidden();

        $this->actingAs($user)->put("/products/{$foreign->id}", ['name' => 'Хак'])->assertForbidden();
        $this->actingAs($user)->delete("/products/{$foreign->id}")->assertForbidden();
        $this->actingAs($user)->delete("/products/{$shared->id}")->assertForbidden();
    }

    public function test_user_can_edit_own_product(): void
    {
        $user = User::factory()->create();

        $product = Product::create([
            'user_id' => $user->id,
            'name' => 'Мой',
            'brand' => null,
            'calories' => 1,
            'protein' => 1,
            'fat' => 1,
            'carbs' => 1,
        ]);

        $this->actingAs($user)
            ->put("/products/{$product->id}", [
                'name' => 'Обновлённый',
                'brand' => null,
                'calories' => 2,
                'protein' => 2,
                'fat' => 2,
                'carbs' => 2,
            ])
            ->assertRedirect("/products/{$product->id}");

        $this->assertSame('Обновлённый', $product->fresh()->name);
    }

    public function test_product_store_sets_current_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/products', [
            'name' => 'Новый продукт',
            'brand' => null,
            'calories' => 3,
            'protein' => 3,
            'fat' => 3,
            'carbs' => 3,
        ])->assertRedirect();

        $this->assertDatabaseHas('products', [
            'name' => 'Новый продукт',
            'user_id' => $user->id,
        ]);
    }

    public function test_user_sees_own_and_shared_recipes_only(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        Recipe::create(['user_id' => $user->id, 'name' => 'Мой рецепт', 'status' => 'to_cook']);
        Recipe::create(['user_id' => null, 'name' => 'Общий рецепт', 'status' => 'to_cook']);
        Recipe::create(['user_id' => $other->id, 'name' => 'Чужой рецепт', 'status' => 'to_cook']);

        $this->actingAs($user)
            ->get('/')
            ->assertSee('Мой рецепт')
            ->assertSee('Общий рецепт')
            ->assertDontSee('Чужой рецепт');
    }

    public function test_guest_cannot_view_private_recipe_show(): void
    {
        $other = User::factory()->create();

        $recipe = Recipe::create(['user_id' => $other->id, 'name' => 'Приватный', 'status' => 'to_cook']);

        $this->get("/recipes/{$recipe->id}")->assertNotFound();
    }

    public function test_preparation_store_sets_current_user_and_scopes_show(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $recipe = Recipe::create(['user_id' => $user->id, 'name' => 'Мой рецепт', 'status' => 'to_cook']);

        $this->actingAs($user)->post("/recipes/{$recipe->id}/preparations", [
            'prepared_at' => '2026-08-16',
            'total_weight' => 500,
            'notes' => null,
            'ingredients' => [],
        ])->assertSessionHasErrors('ingredients');

        $response = $this->actingAs($other)
            ->post("/recipes/{$recipe->id}/preparations", [
                'prepared_at' => '2026-08-16',
                'total_weight' => 400,
                'ingredients' => [],
            ]);

        $response->assertNotFound();

        $preparation = Preparation::create([
            'recipe_id' => $recipe->id,
            'user_id' => $other->id,
            'prepared_at' => '2026-08-16',
            'total_weight' => 400,
        ]);

        $this->actingAs($user)->get("/preparations/{$preparation->id}")->assertNotFound();
    }

    public function test_user_can_add_own_preparation_to_shared_recipe(): void
    {
        $user = User::factory()->create();

        $recipe = Recipe::create(['user_id' => null, 'name' => 'Общий рецепт', 'status' => 'to_cook']);

        $this->actingAs($user)
            ->get("/recipes/{$recipe->id}/preparations/create")
            ->assertOk()
            ->assertSee('Общий рецепт');

        $this->actingAs($user)
            ->post("/recipes/{$recipe->id}/preparations", [
                'prepared_at' => '2026-08-16',
                'total_weight' => 500,
                'ingredients' => [],
            ])
            ->assertSessionHasErrors('ingredients');
    }

    public function test_recipe_can_reference_shared_product_in_ingredients(): void
    {
        $user = User::factory()->create();

        $shared = Product::create([
            'user_id' => null,
            'name' => 'Общий продукт',
            'brand' => null,
            'calories' => 10,
            'protein' => 1,
            'fat' => 1,
            'carbs' => 1,
        ]);

        $this->actingAs($user)->post('/recipes', [
            'name' => 'Рецепт с общим продуктом',
            'status' => 'to_cook',
            'ingredients' => [
                ['product_id' => $shared->id, 'amount' => 100, 'unit' => 'g'],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('recipes', ['name' => 'Рецепт с общим продуктом', 'user_id' => $user->id]);
        $this->assertDatabaseHas('recipe_ingredients', ['product_id' => $shared->id]);
    }

    public function test_recipe_cannot_reference_foreign_private_product(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $foreign = Product::create([
            'user_id' => $other->id,
            'name' => 'Чужой продукт',
            'brand' => null,
            'calories' => 10,
            'protein' => 1,
            'fat' => 1,
            'carbs' => 1,
        ]);

        $this->actingAs($user)->post('/recipes', [
            'name' => 'Рецепт',
            'status' => 'to_cook',
            'ingredients' => [
                ['product_id' => $foreign->id, 'amount' => 100, 'unit' => 'g'],
            ],
        ])->assertSessionHasErrors('ingredients.0.product_id');

        $this->assertDatabaseMissing('recipes', ['name' => 'Рецепт']);
    }
}
