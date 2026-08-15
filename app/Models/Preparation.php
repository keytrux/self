<?php

namespace App\Models;

use App\Models\Concerns\UserScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Preparation extends Model
{
    use HasFactory, UserScoped;

    protected $fillable = [
        'recipe_id',
        'user_id',
        'prepared_at',
        'total_weight',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'prepared_at' => 'date',
            'total_weight' => 'float',
        ];
    }

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ingredients(): HasMany
    {
        return $this->hasMany(PreparationIngredient::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(Media::class);
    }

    public function calories(): float
    {
        return $this->ingredients->sum(fn ($i) => $i->calories());
    }

    public function protein(): float
    {
        return $this->ingredients->sum(fn ($i) => $i->protein());
    }

    public function fat(): float
    {
        return $this->ingredients->sum(fn ($i) => $i->fat());
    }

    public function carbs(): float
    {
        return $this->ingredients->sum(fn ($i) => $i->carbs());
    }

    public function caloriesPer100(): float
    {
        return $this->total_weight > 0
            ? $this->calories() / $this->total_weight * 100
            : 0;
    }

    public function proteinPer100(): float
    {
        return $this->total_weight > 0
            ? $this->protein() / $this->total_weight * 100
            : 0;
    }

    public function fatPer100(): float
    {
        return $this->total_weight > 0
            ? $this->fat() / $this->total_weight * 100
            : 0;
    }

    public function carbsPer100(): float
    {
        return $this->total_weight > 0
            ? $this->carbs() / $this->total_weight * 100
            : 0;
    }

    public function kbjuForPortion(float $weight): array
    {
        return [
            'calories' => $this->caloriesPer100() * $weight / 100,
            'protein' => $this->proteinPer100() * $weight / 100,
            'fat' => $this->fatPer100() * $weight / 100,
            'carbs' => $this->carbsPer100() * $weight / 100,
        ];
    }
}
