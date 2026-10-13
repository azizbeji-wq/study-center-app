<?php

namespace App\Policies;

use App\Models\Group;
use App\Models\User;

class GroupPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Group $group): bool
    {
        if (in_array(strtolower($user->role), ['director', 'directeur', 'admin', 'secretary', 'secretaire'])) {
            return true;
        }

        return $group->teacher_id === $user->id || $group->teacher_id === $user->teacher?->id;
    }

    /**
     * Determine whether the user can create models.
     * Accessible aux directeurs, secrétaires et enseignants.
     */
    public function create(User $user): bool
    {
        return in_array(strtolower($user->role), [
            'director', 'directeur', 'admin',
            'secretary', 'secretaire',
            'teacher', 'enseignant', 'professeur'
        ]);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Group $group): bool
    {
        if (in_array(strtolower($user->role), ['director', 'directeur', 'admin', 'secretary', 'secretaire'])) {
            return true;
        }

        return $group->teacher_id === $user->id || $group->teacher_id === $user->teacher?->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Group $group): bool
    {
        return in_array(strtolower($user->role), ['director', 'directeur', 'admin']);
    }
}
