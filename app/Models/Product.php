<?php

namespace App\Models;

use App\Models\Concerns\UserScoped;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory, UserScoped;

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    protected $fillable = [
        'user_id',
        'name',
        'brand',
        'barcode',
        'calories',
        'protein',
        'fat',
        'carbs',
        'image',
        'source_url',
        'notes',
        'is_public',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'calories' => 'float',
            'protein' => 'float',
            'fat' => 'float',
            'carbs' => 'float',
            'is_public' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function recipeIngredients(): HasMany
    {
        return $this->hasMany(RecipeIngredient::class);
    }

    public function preparationIngredients(): HasMany
    {
        return $this->hasMany(PreparationIngredient::class);
    }

    public function kbjuLabel(): string
    {
        return "{$this->calories} ккал / {$this->protein} Б / {$this->fat} Ж / {$this->carbs} У";
    }
}
