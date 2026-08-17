<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Preparation;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RecipeCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_barcode_must_be_unique(): void
    {
        $user = User::factory()->create();

        Product::create([
            'user_id' => $user->id,
            'name' => 'Сыр',
            'calories' => 300,
            'protein' => 20,
            'fat' => 25,
            'carbs' => 1,
            'barcode' => '4600000000000',
        ]);

        $this->actingAs($user)
            ->post('/products', [
                'name' => 'Другой сыр',
                'calories' => 100,
                'protein' => 1,
                'fat' => 1,
                'carbs' => 1,
                'barcode' => '4600000000000',
            ])
            ->assertSessionHasErrors('barcode');
    }

    public function test_barcode_nullable_and_searchable(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/products', [
                'name' => 'Молоко',
                'calories' => 60,
                'protein' => 3,
                'fat' => 3,
                'carbs' => 5,
            ])
            ->assertRedirect();

        $this->get('/products?search=Молоко')->assertSee('Молоко');
    }

    public function test_destroy_archives_product_used_in_recipe(): void
    {
        $user = User::factory()->create();

        $product = Product::create([
            'user_id' => $user->id,
            'name' => 'Курица',
            'calories' => 110,
            'protein' => 20,
            'fat' => 3,
            'carbs' => 0,
        ]);

        $recipe = Recipe::create(['user_id' => $user->id, 'name' => 'Курица варёная']);
        $recipe->ingredients()->create([
            'product_id' => $product->id,
            'amount' => 500,
            'unit' => 'g',
        ]);

        $this->actingAs($user)
            ->delete("/products/{$product->id}")
            ->assertRedirect();

        $this->assertDatabaseHas('products', ['id' => $product->id, 'is_active' => false]);
        $this->assertDatabaseHas('recipe_ingredients', ['recipe_id' => $recipe->id, 'product_id' => $product->id]);

        $this->get('/products')->assertDontSee('Курица');
        $this->get('/products?include_archived=1')->assertSee('Курица');
    }

    public function test_destroy_deletes_unused_product(): void
    {
        $user = User::factory()->create();

        $product = Product::create([
            'user_id' => $user->id,
            'name' => 'Соль',
            'calories' => 0,
            'protein' => 0,
            'fat' => 0,
            'carbs' => 0,
        ]);

        $this->actingAs($user)
            ->delete("/products/{$product->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_archive_and_restore_product(): void
    {
        $user = User::factory()->create();

        $product = Product::create([
            'user_id' => $user->id,
            'name' => 'Гречка',
            'calories' => 340,
            'protein' => 12,
            'fat' => 3,
            'carbs' => 60,
        ]);

        $this->actingAs($user)
            ->post("/products/{$product->id}/archive")
            ->assertRedirect();

        $this->assertDatabaseHas('products', ['id' => $product->id, 'is_active' => false]);

        $this->actingAs($user)
            ->post("/products/{$product->id}/restore")
            ->assertRedirect();

        $this->assertDatabaseHas('products', ['id' => $product->id, 'is_active' => true]);
    }

    public function test_user_cannot_create_public_product(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/products', [
                'name' => 'Попытка',
                'calories' => 1,
                'protein' => 1,
                'fat' => 1,
                'carbs' => 1,
                'is_public' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('products', ['name' => 'Попытка', 'user_id' => $user->id, 'is_public' => false]);
    }

    public function test_admin_can_create_public_product(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->post('/products', [
                'name' => 'Общее мясо',
                'calories' => 200,
                'protein' => 20,
                'fat' => 12,
                'carbs' => 0,
                'is_public' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('products', ['name' => 'Общее мясо', 'is_public' => true]);
    }

    public function test_user_cannot_create_public_recipe(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/recipes', [
                'name' => 'Публичная попытка',
                'is_public' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('recipes', ['name' => 'Публичная попытка', 'user_id' => $user->id, 'is_public' => false]);
    }

    public function test_admin_can_create_public_recipe_and_everyone_sees_it(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();

        $product = Product::create([
            'user_id' => $admin->id,
            'name' => 'Сыр',
            'calories' => 316,
            'protein' => 25,
            'fat' => 24,
            'carbs' => 0,
        ]);

        $this->actingAs($admin)
            ->post('/recipes', [
                'name' => 'Публичный суп',
                'is_public' => 1,
                'ingredients' => [
                    ['product_id' => $product->id, 'amount' => 100, 'unit' => 'g'],
                ],
            ])
            ->assertRedirect();

        $recipe = Recipe::where('name', 'Публичный суп')->firstOrFail();
        $this->assertTrue($recipe->is_public);

        $this->actingAs($user)
            ->get("/recipes/{$recipe->id}")
            ->assertOk()
            ->assertSee('Публичный суп');
    }

    public function test_user_cannot_edit_public_recipe(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();

        $recipe = Recipe::create([
            'user_id' => null,
            'name' => 'Публичный рецепт',
            'is_public' => true,
        ]);

        $this->actingAs($user)
            ->put("/recipes/{$recipe->id}", ['name' => 'Изменённый'])
            ->assertForbidden();

        $this->assertDatabaseHas('recipes', ['id' => $recipe->id, 'name' => 'Публичный рецепт']);
    }

    public function test_user_can_fork_public_recipe(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();

        $product = Product::create([
            'user_id' => $admin->id,
            'name' => 'Курица',
            'calories' => 110,
            'protein' => 20,
            'fat' => 3,
            'carbs' => 0,
        ]);

        $recipe = Recipe::create([
            'user_id' => null,
            'name' => 'Курица по-французски',
            'is_public' => true,
        ]);
        $recipe->ingredients()->create(['product_id' => $product->id, 'amount' => 600, 'unit' => 'g', 'sort_order' => 0]);

        $this->actingAs($user)
            ->post("/recipes/{$recipe->id}/fork")
            ->assertRedirect();

        $copy = Recipe::where('name', 'Курица по-французски')
            ->where('user_id', $user->id)
            ->firstOrFail();
        $this->assertFalse($copy->is_public);
        $this->assertCount(1, $copy->ingredients);
        $this->assertSame($product->id, $copy->ingredients->first()->product_id);

        $this->assertDatabaseHas('recipes', ['id' => $recipe->id, 'user_id' => null]);
        $this->assertDatabaseCount('recipes', 2);
    }

    public function test_user_cannot_fork_own_recipe(): void
    {
        $user = User::factory()->create();
        $recipe = Recipe::create(['user_id' => $user->id, 'name' => 'Мой рецепт']);

        $this->actingAs($user)
            ->post("/recipes/{$recipe->id}/fork")
            ->assertForbidden();
    }

    public function test_favorite_toggle_and_filter(): void
    {
        $user = User::factory()->create();

        $recipe = Recipe::create(['user_id' => $user->id, 'name' => 'Избранный рецепт']);
        $other = Recipe::create(['user_id' => $user->id, 'name' => 'Обычный рецепт']);

        $this->actingAs($user)
            ->post("/recipes/{$recipe->id}/favorite")
            ->assertRedirect();

        $this->assertDatabaseHas('favorites', ['user_id' => $user->id, 'recipe_id' => $recipe->id]);

        $this->actingAs($user)
            ->get('/?favorites=1')
            ->assertSee('Избранный рецепт')
            ->assertDontSee('Обычный рецепт');

        $this->actingAs($user)
            ->post("/recipes/{$recipe->id}/favorite")
            ->assertRedirect();

        $this->assertDatabaseMissing('favorites', ['user_id' => $user->id, 'recipe_id' => $recipe->id]);
    }

    public function test_category_filter_and_assignment(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/recipes', [
                'name' => 'Сытный рецепт',
                'category_id' => Category::create(['name' => 'Супы'])->id,
            ])
            ->assertRedirect();

        $recipe = Recipe::where('name', 'Сытный рецепт')->firstOrFail();
        $this->assertNotNull($recipe->category_id);

        $this->actingAs($user)
            ->get('/?category='.$recipe->category_id)
            ->assertSee('Сытный рецепт');
    }

    public function test_preparation_saves_kbju_snapshot_and_keeps_history(): void
    {
        $user = User::factory()->create();

        $product = Product::create([
            'user_id' => $user->id,
            'name' => 'Сыр',
            'calories' => 316,
            'protein' => 25,
            'fat' => 24,
            'carbs' => 0,
        ]);

        $recipe = Recipe::create(['user_id' => $user->id, 'name' => 'Сырная запеканка']);

        $this->actingAs($user)
            ->post("/recipes/{$recipe->id}/preparations", [
                'prepared_at' => '2026-08-15',
                'total_weight' => 200,
                'notes' => null,
                'ingredients' => [
                    ['product_id' => $product->id, 'amount' => 72, 'unit' => 'g'],
                ],
            ])
            ->assertRedirect();

        $preparation = $recipe->preparations()->firstOrFail();
        $ingredient = $preparation->ingredients()->firstOrFail();

        $this->assertSame(316.0, (float) $ingredient->calories);
        $this->assertSame(25.0, (float) $ingredient->protein);
        $this->assertSame(24.0, (float) $ingredient->fat);
        $this->assertSame(0.0, (float) $ingredient->carbs);

        $expectedTotal = 316 * 72 / 100;
        $this->assertEqualsWithDelta($expectedTotal, $preparation->calories(), 0.01);

        $product->update(['calories' => 500]);

        $this->assertEqualsWithDelta($expectedTotal, $preparation->calories(), 0.01);
        $this->assertEqualsWithDelta($expectedTotal / 200 * 100, $preparation->caloriesPer100(), 0.01);
    }

    public function test_preparation_ingredients_can_differ_from_recipe(): void
    {
        $user = User::factory()->create();

        $chicken = Product::create([
            'user_id' => $user->id, 'name' => 'Курица', 'calories' => 110,
            'protein' => 20, 'fat' => 3, 'carbs' => 0,
        ]);
        $cauliflower = Product::create([
            'user_id' => $user->id, 'name' => 'Цветная капуста', 'calories' => 25,
            'protein' => 2, 'fat' => 0, 'carbs' => 5,
        ]);

        $recipe = Recipe::create(['user_id' => $user->id, 'name' => 'Рагу']);
        $recipe->ingredients()->create(['product_id' => $chicken->id, 'amount' => 600, 'unit' => 'g', 'sort_order' => 0]);

        $this->actingAs($user)
            ->post("/recipes/{$recipe->id}/preparations", [
                'prepared_at' => '2026-08-16',
                'total_weight' => 2100,
                'ingredients' => [
                    ['product_id' => $chicken->id, 'amount' => 550, 'unit' => 'g'],
                    ['product_id' => $cauliflower->id, 'amount' => 300, 'unit' => 'g'],
                ],
            ])
            ->assertRedirect();

        $preparation = $recipe->preparations()->firstOrFail();
        $this->assertCount(2, $preparation->ingredients);
        $this->assertCount(1, $recipe->ingredients);

        $this->assertEqualsWithDelta(
            110 * 550 / 100 + 25 * 300 / 100,
            $preparation->calories(),
            0.01
        );
    }

    public function test_index_shows_derived_status_counts(): void
    {
        $user = User::factory()->create();

        $toCook = Recipe::create(['user_id' => $user->id, 'name' => 'Не готовил']);
        $cooked = Recipe::create(['user_id' => $user->id, 'name' => 'Готовил']);

        Preparation::create([
            'recipe_id' => $cooked->id,
            'user_id' => $user->id,
            'prepared_at' => '2026-08-10',
            'total_weight' => 900,
        ]);

        $this->actingAs($user)
            ->get('/')
            ->assertSee('Хочу приготовить (1)')
            ->assertSee('Приготовлено (1)');

        $this->actingAs($user)
            ->get('/?status=cooked')
            ->assertSee('Готовил')
            ->assertDontSee('Не готовил');

        $this->actingAs($user)
            ->get('/?status=to_cook')
            ->assertSee('Не готовил')
            ->assertDontSee('Готовил');
    }

    public function test_password_reset_link_and_reset(): void
    {
        $user = User::factory()->create(['email' => 'reset@example.com']);

        $this->from('/forgot-password')->post('/forgot-password', [
            'email' => 'reset@example.com',
        ])->assertRedirect('/forgot-password');

        $this->assertDatabaseHas('password_reset_tokens', ['email' => 'reset@example.com']);

        $token = Password::createToken($user);

        $this->post('/reset-password', [
            'token' => $token,
            'email' => 'reset@example.com',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));
    }

    public function test_product_search_returns_only_own_and_shared(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $own = Product::create([
            'user_id' => $user->id, 'name' => 'Сливки 10%', 'brand' => 'Бренд А',
            'calories' => 100, 'protein' => 3, 'fat' => 7, 'carbs' => 4,
        ]);
        $shared = Product::create([
            'user_id' => null, 'name' => 'Сливки 20%', 'brand' => null,
            'calories' => 120, 'protein' => 3, 'fat' => 9, 'carbs' => 4,
        ]);
        $foreign = Product::create([
            'user_id' => $other->id, 'name' => 'Сливочный сыр', 'brand' => null,
            'calories' => 200, 'protein' => 8, 'fat' => 18, 'carbs' => 2,
        ]);
        Product::create([
            'user_id' => $other->id, 'name' => 'Внешние сливки', 'brand' => null,
            'calories' => 50, 'protein' => 1, 'fat' => 1, 'carbs' => 1,
        ]);

        $response = $this->actingAs($user)->getJson('/products/search?q=Сли');

        $response->assertOk();
        $response->assertJsonFragment(['id' => $own->id, 'name' => 'Сливки 10%']);
        $response->assertJsonFragment(['id' => $shared->id, 'name' => 'Сливки 20%']);
        $response->assertJsonMissing(['name' => 'Сливочный сыр']);
        $response->assertJsonMissing(['name' => 'Внешние сливки']);

        $ids = collect($response->json())->pluck('id')->all();
        $this->assertNotContains($foreign->id, $ids);
    }

    public function test_product_search_by_brand_and_barcode(): void
    {
        $user = User::factory()->create();

        Product::create([
            'user_id' => $user->id, 'name' => 'Молоко', 'brand' => 'Простоквашино',
            'calories' => 60, 'protein' => 3, 'fat' => 3, 'carbs' => 5, 'barcode' => '4600000000001',
        ]);

        $this->actingAs($user)->getJson('/products/search?q=Простоквашино')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Молоко']);

        $this->actingAs($user)->getJson('/products/search?q=4600000000001')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Молоко']);
    }

    public function test_product_search_requires_auth(): void
    {
        $this->getJson('/products/search?q=сли')->assertUnauthorized();
    }

    public function test_product_search_limits_results_to_20(): void
    {
        $user = User::factory()->create();

        for ($i = 1; $i <= 25; $i++) {
            Product::create([
                'user_id' => $user->id, 'name' => "Сливки {$i}", 'brand' => null,
                'calories' => 100, 'protein' => 3, 'fat' => 7, 'carbs' => 4,
            ]);
        }

        $response = $this->actingAs($user)->getJson('/products/search?q=Сливки');

        $this->assertCount(20, $response->json());
    }

    public function test_product_search_excludes_archived(): void
    {
        $user = User::factory()->create();

        $active = Product::create([
            'user_id' => $user->id, 'name' => 'Активный сыр', 'brand' => null,
            'calories' => 100, 'protein' => 3, 'fat' => 7, 'carbs' => 4,
        ]);
        $archived = Product::create([
            'user_id' => $user->id, 'name' => 'Архивный сыр', 'brand' => null,
            'calories' => 100, 'protein' => 3, 'fat' => 7, 'carbs' => 4, 'is_active' => false,
        ]);

        $this->actingAs($user)->getJson('/products/search?q=сыр')
            ->assertOk()
            ->assertJsonFragment(['id' => $active->id])
            ->assertJsonMissing(['id' => $archived->id]);
    }

    public function test_ingredient_new_product_created_as_personal(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/recipes', [
                'name' => 'Рецепт с новым продуктом',
                'ingredients' => [
                    [
                        'product_id' => 'new',
                        'new_name' => 'Сливочное масло',
                        'new_brand' => null,
                        'new_calories' => 748,
                        'new_protein' => 0.8,
                        'new_fat' => 82.5,
                        'new_carbs' => 0.8,
                        'amount' => 50,
                        'unit' => 'g',
                    ],
                ],
            ])
            ->assertRedirect();

        $product = Product::where('name', 'Сливочное масло')->firstOrFail();
        $this->assertSame($user->id, $product->user_id);
        $this->assertFalse($product->is_public);
    }

    public function test_recipe_accepts_video_file_and_renders_in_carousel(): void
    {
        $user = User::factory()->create();
        Storage::fake('public');

        $mp4 = hex2bin('0000001c667479706d703432000000006d70343269736f6d');
        $file = UploadedFile::fake()->createWithContent('clip.mp4', $mp4);

        $this->actingAs($user)
            ->post('/recipes', [
                'name' => 'Рецепт с видео',
                'video_files' => [$file],
            ])
            ->assertRedirect();

        $recipe = Recipe::where('name', 'Рецепт с видео')->firstOrFail();
        $media = $recipe->media()->where('type', 'video')->whereNotNull('path')->firstOrFail();

        Storage::disk('public')->assertExists($media->path);

        $this->actingAs($user)
            ->get("/recipes/{$recipe->id}")
            ->assertSee('Фото и видео')
            ->assertSee('<video', false);
    }

    public function test_video_file_rejected_if_not_video(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/recipes', [
                'name' => 'Рецепт с мусором',
                'video_files' => [UploadedFile::fake()->create('evil.exe', 100)],
            ])
            ->assertSessionHasErrors('video_files.0');
    }

    public function test_video_file_copied_on_fork(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();
        Storage::fake('public');

        $recipe = Recipe::create(['user_id' => null, 'name' => 'Публичный с видео', 'is_public' => true]);
        $recipe->media()->create(['type' => 'video', 'path' => 'recipe-videos/original.mp4']);
        Storage::disk('public')->put('recipe-videos/original.mp4', 'video');

        $this->actingAs($user)
            ->post("/recipes/{$recipe->id}/fork")
            ->assertRedirect();

        $copy = Recipe::where('name', 'Публичный с видео')->where('user_id', $user->id)->firstOrFail();
        $this->assertCount(1, $copy->videoFiles());
        $this->assertNotSame('recipe-videos/original.mp4', $copy->videoFiles()->first()->path);
        Storage::disk('public')->assertExists($copy->videoFiles()->first()->path);
        Storage::disk('public')->assertExists('recipe-videos/original.mp4');
    }
}
