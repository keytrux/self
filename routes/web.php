<?php

use App\Http\Controllers\PreparationController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RecipeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [RecipeController::class, 'index'])->name('recipes.index');

Route::get('recipes/create', [RecipeController::class, 'create'])->name('recipes.create');
Route::post('recipes', [RecipeController::class, 'store'])->name('recipes.store');
Route::get('recipes/{recipe}', [RecipeController::class, 'show'])->name('recipes.show');

Route::get('recipes/{recipe}/preparations/create', [PreparationController::class, 'create'])->name('preparations.create');
Route::post('recipes/{recipe}/preparations', [PreparationController::class, 'store'])->name('preparations.store');
Route::get('preparations/{preparation}', [PreparationController::class, 'show'])->name('preparations.show');

Route::get('products', [ProductController::class, 'index'])->name('products.index');
Route::get('products/create', [ProductController::class, 'create'])->name('products.create');
Route::post('products', [ProductController::class, 'store'])->name('products.store');
Route::get('products/{product}', [ProductController::class, 'show'])->name('products.show');
