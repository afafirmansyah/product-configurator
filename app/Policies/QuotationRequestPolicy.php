<?php

namespace App\Policies;

use App\Models\QuotationRequest;
use App\Models\User;

class QuotationRequestPolicy
{
    /**
     * Roles permitted to view any quotation regardless of ownership.
     *
     * @var list<string>
     */
    private const INTERNAL_ROLES = ['super_admin', 'product_manager', 'engineering', 'sales'];

    /**
     * Determine whether the user can view (and download) the quotation.
     */
    public function view(User $user, QuotationRequest $quotation): bool
    {
        if ($quotation->user_id !== null && $quotation->user_id === $user->id) {
            return true;
        }

        return $user->hasAnyRole(self::INTERNAL_ROLES);
    }
}
