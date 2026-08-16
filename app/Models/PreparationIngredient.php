<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PreparationIngredient extends Model
{
    use HasFactory;

    protected $fillable = [
        'preparation_id',
        'product_id',
        'amount',
        'unit',
        'calories',
        'protein',
        'fat',
        'carbs',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'float',
            'calories' => 'float',
            'protein' => 'float',
            'fat' => 'float',
            'carbs' => 'float',
        ];
    }

    public function preparation(): BelongsTo
    {
        return $this->belongsTo(Preparation::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * КБЖУ ингредиента считается от сохранённого snapshot,
     * чтобы история не менялась при изменении продукта.
     */
    public function calories(): float
    {
        return $this->calories * $this->amount / 100;
    }

    public function protein(): float
    {
        return $this->protein * $this->amount / 100;
    }

    public function fat(): float
    {
        return $this->fat * $this->amount / 100;
    }

    public function carbs(): float
    {
        return $this->carbs * $this->amount / 100;
    }
}
