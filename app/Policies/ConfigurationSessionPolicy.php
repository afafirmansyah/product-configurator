<?php

namespace App\Policies;

use App\Models\ConfigurationSession;
use App\Models\User;

class ConfigurationSessionPolicy
{
    /**
     * Roles permitted to access any configuration session regardless of ownership.
     *
     * @var list<string>
     */
    private const INTERNAL_ROLES = ['super_admin', 'product_manager', 'engineering'];

    /**
     * Determine whether the user can view the configuration session.
     */
    public function view(User $user, ConfigurationSession $session): bool
    {
        return $this->ownsOrIsInternal($user, $session);
    }

    /**
     * Determine whether the user can request a quotation for the configuration session.
     */
    public function createQuotation(User $user, ConfigurationSession $session): bool
    {
        return $this->ownsOrIsInternal($user, $session);
    }

    /**
     * Determine whether the user owns the session or holds an internal role.
     */
    private function ownsOrIsInternal(User $user, ConfigurationSession $session): bool
    {
        if ($session->user_id !== null && $session->user_id === $user->id) {
            return true;
        }

        return $user->hasAnyRole(self::INTERNAL_ROLES);
    }
}
