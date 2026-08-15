<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Recipe extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'instructions',
        'notes',
        'status',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ingredients(): HasMany
    {
        return $this->hasMany(RecipeIngredient::class);
    }

    public function preparations(): HasMany
    {
        return $this->hasMany(Preparation::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(Media::class);
    }

    public function lastPreparation(): ?Preparation
    {
        return $this->preparations
            ->sortByDesc('prepared_at')
            ->first();
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'to_cook' => 'Хочу приготовить',
            'cooked' => 'Приготовлено',
            'liked' => 'Понравилось',
            'disliked' => 'Не понравилось',
            default => $this->status,
        };
    }

    public function statusEmoji(): string
    {
        return match ($this->status) {
            'to_cook' => '📝',
            'cooked' => '🍳',
            'liked' => '❤️',
            'disliked' => '👎',
            default => '',
        };
    }

    public function coverImage(): ?Media
    {
        return $this->media
            ->where('type', 'photo')
            ->first();
    }

    public function photos(): \Illuminate\Support\Collection
    {
        return $this->media->where('type', 'photo')->values();
    }

    public function videos(): \Illuminate\Support\Collection
    {
        return $this->media->where('type', 'video')->values();
    }

    public function links(): \Illuminate\Support\Collection
    {
        return $this->media->where('type', 'link')->values();
    }
}
