<?php

namespace App\Models;

use App\Models\Concerns\UserScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Recipe extends Model
{
    use HasFactory, UserScoped;

    protected $fillable = [
        'user_id',
        'category_id',
        'name',
        'description',
        'instructions',
        'notes',
        'status',
        'is_public',
    ];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function ingredients(): HasMany
    {
        return $this->hasMany(RecipeIngredient::class)->orderBy('sort_order');
    }

    public function preparations(): HasMany
    {
        return $this->hasMany(Preparation::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(Media::class);
    }

    public function favoritedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorites', 'recipe_id', 'user_id')
            ->withTimestamps();
    }

    public function isFavoritedBy(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        return $this->favoritedByUsers()->whereKey($user->id)->exists();
    }

    public function isCooked(): bool
    {
        return $this->preparations->isNotEmpty();
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

    public function photos(): Collection
    {
        return $this->media->where('type', 'photo')->values();
    }

    public function videos(): Collection
    {
        return $this->media->where('type', 'video')->values();
    }

    public function videoLinks(): Collection
    {
        return $this->media
            ->where('type', 'video')
            ->whereNotNull('url')
            ->values();
    }

    public function videoFiles(): Collection
    {
        return $this->media
            ->where('type', 'video')
            ->whereNotNull('path')
            ->values();
    }

    public function links(): Collection
    {
        return $this->media->where('type', 'link')->values();
    }
}
