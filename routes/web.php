<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\PreparationController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RecipeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [RecipeController::class, 'index'])->name('recipes.index');

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login']);
    Route::get('register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('register', [AuthController::class, 'register']);

    Route::get('forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');
    Route::get('reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('reset-password', [AuthController::class, 'resetPassword'])->name('password.store');
});

Route::post('logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

Route::middleware('auth')->group(function () {
    Route::get('recipes/create', [RecipeController::class, 'create'])->name('recipes.create');
    Route::post('recipes', [RecipeController::class, 'store'])->name('recipes.store');
    Route::post('recipes/{recipe}/fork', [RecipeController::class, 'fork'])->name('recipes.fork');
    Route::post('recipes/{recipe}/favorite', [RecipeController::class, 'toggleFavorite'])->name('recipes.toggle-favorite');
    Route::get('recipes/{recipe}/edit', [RecipeController::class, 'edit'])->name('recipes.edit');
    Route::match(['put', 'patch'], 'recipes/{recipe}', [RecipeController::class, 'update'])->name('recipes.update');
    Route::delete('recipes/{recipe}', [RecipeController::class, 'destroy'])->name('recipes.destroy');

    Route::get('recipes/{recipe}/preparations/create', [PreparationController::class, 'create'])->name('preparations.create');
    Route::post('recipes/{recipe}/preparations', [PreparationController::class, 'store'])->name('preparations.store');
    Route::get('preparations/{preparation}/edit', [PreparationController::class, 'edit'])->name('preparations.edit');
    Route::match(['put', 'patch'], 'preparations/{preparation}', [PreparationController::class, 'update'])->name('preparations.update');
    Route::delete('preparations/{preparation}', [PreparationController::class, 'destroy'])->name('preparations.destroy');

    Route::get('products/search', [ProductController::class, 'search'])->name('products.search');
    Route::get('products/create', [ProductController::class, 'create'])->name('products.create');
    Route::post('products', [ProductController::class, 'store'])->name('products.store');
    Route::get('products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
    Route::match(['put', 'patch'], 'products/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::post('products/{product}/archive', [ProductController::class, 'archive'])->name('products.archive');
    Route::post('products/{product}/restore', [ProductController::class, 'restore'])->name('products.restore');
    Route::delete('products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');

    Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::post('categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::match(['put', 'patch'], 'categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
});

// Публичный просмотр (индексы и show). Скоупы по владельцу + «общим» в контроллерах.
Route::get('recipes/{recipe}', [RecipeController::class, 'show'])->name('recipes.show');
Route::get('products', [ProductController::class, 'index'])->name('products.index');
Route::get('products/{product}', [ProductController::class, 'show'])->name('products.show');
Route::get('preparations/{preparation}', [PreparationController::class, 'show'])->name('preparations.show');
