<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecipeIngredient extends Model
{
    use HasFactory;

    protected $fillable = [
        'recipe_id',
        'product_id',
        'amount',
        'unit',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'float',
        ];
    }

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function calories(): float
    {
        return $this->product->calories * $this->amount / 100;
    }

    public function protein(): float
    {
        return $this->product->protein * $this->amount / 100;
    }

    public function fat(): float
    {
        return $this->product->fat * $this->amount / 100;
    }

    public function carbs(): float
    {
        return $this->product->carbs * $this->amount / 100;
    }
}
