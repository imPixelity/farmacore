<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('core.users.view');
    }

    public function create(User $user): bool
    {
        return $user->can('core.users.create');
    }

    public function update(User $user, User $model): bool
    {
        return $user->can('core.users.update');
    }

    public function deactivate(User $user, User $model): bool
    {
        return $user->id !== $model->id && $user->can('core.users.deactivate');
    }
}
