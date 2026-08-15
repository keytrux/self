<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

trait UserScoped
{
    /**
     * Ограничить выборку сущностями владельца + общими (user_id IS NULL).
     */
    public function scopeVisibleToUser(Builder $query, ?int $userId = null): Builder
    {
        return $query->where(function (Builder $query) use ($userId) {
            $query->whereNull('user_id');

            if ($userId !== null) {
                $query->orWhere('user_id', $userId);
            }
        });
    }

    public function isVisibleTo(?User $user): bool
    {
        return $this->user_id === null
            || ($user !== null && (int) $this->user_id === (int) $user->id);
    }

    public function isOwnedBy(?User $user): bool
    {
        return $this->user_id !== null
            && $user !== null
            && (int) $this->user_id === (int) $user->id;
    }
}
