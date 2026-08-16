<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

trait UserScoped
{
    /**
     * Ограничить выборку: свои + общие (user_id IS NULL).
     *
     * Для моделей с полем is_public (Product, Recipe) дополнительно
     * включаются публичные сущности.
     */
    public function scopeVisibleToUser(Builder $query, ?int $userId = null): Builder
    {
        return $query->where(function (Builder $query) use ($userId) {
            $query
                ->whereNull('user_id')
                ->when($this->hasPublicFlag(), fn ($q) => $q->orWhere('is_public', true));

            if ($userId !== null) {
                $query->orWhere('user_id', $userId);
            }
        });
    }

    public function isVisibleTo(?User $user): bool
    {
        if ($this->isPublicEntity()) {
            return true;
        }

        return $this->user_id === null
            || ($user !== null && (int) $this->user_id === (int) $user->id);
    }

    /**
     * Редактировать может владелец. Публичные/общие сущности — только администратор.
     */
    public function isOwnedBy(?User $user): bool
    {
        return $this->user_id !== null
            && $user !== null
            && (int) $this->user_id === (int) $user->id;
    }

    public function isManagedBy(?User $user): bool
    {
        if ($this->isOwnedBy($user)) {
            return true;
        }

        if ($user?->is_admin && $this->hasSharedFlag()) {
            return true;
        }

        return $this->user_id === null
            && $user !== null
            && $user->is_admin;
    }

    protected function hasPublicFlag(): bool
    {
        return array_key_exists('is_public', $this->attributes ?? [])
            || method_exists($this, 'getFillable') && in_array('is_public', $this->getFillable());
    }

    protected function isPublicEntity(): bool
    {
        return $this->hasPublicFlag()
            && (bool) ($this->attributes['is_public'] ?? false);
    }

    protected function hasSharedFlag(): bool
    {
        return $this->isPublicEntity() || $this->user_id === null;
    }

    /**
     * Публичная сущность: видна всем пользователям (is_public = true или без владельца).
     */
    public function isShared(): bool
    {
        return $this->hasSharedFlag();
    }
}
