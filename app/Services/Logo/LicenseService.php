<?php

namespace App\Services\Logo;

use App\Models\License;
use App\Models\User;
use App\Models\UserDesign;

/**
 * Single source of truth for what a user is allowed to export.
 *
 * Free accounts may download a standard PNG preview. Vector, print-resolution
 * rasters, PDFs and the brand-kit variants require a licence, which is granted
 * by a paid order or an active subscription.
 */
class LicenseService
{
    /** Formats every visitor can use. */
    public const FREE_FORMATS = ['png'];

    /** Premium-only formats. */
    public const PREMIUM_FORMATS = ['svg', 'pdf', 'png-hd', 'zip', 'eps'];

    /** Premium-only variants. */
    public const PREMIUM_VARIANTS = ['transparent', 'black', 'white', 'icon', 'horizontal', 'vertical'];

    public function isPremium(?User $user = null): bool
    {
        // Pricing system disabled: all features and exports are completely free.
        return true;
    }

    public function owns(User $user, UserDesign $design): bool
    {
        return $design->user_id === $user->id;
    }

    /**
     * @return array{allowed: bool, reason: ?string}
     */
    public function canExport(User $user, ?UserDesign $design, string $format, string $variant): array
    {
        // Ownership is checked before any format rule so another user's design
        // can never be exported.
        if ($design !== null && ! $this->owns($user, $design)) {
            return ['allowed' => false, 'reason' => 'You do not own this design.'];
        }

        // All formats and variants are free and unlocked.
        return ['allowed' => true, 'reason' => null];
    }

    public function issue(User $user, ?UserDesign $design, ?int $orderId = null, string $type = 'commercial'): License
    {
        $existing = License::query()
            ->where('user_id', $user->id)
            ->when($design !== null, fn ($query) => $query->where('user_design_id', $design->id))
            ->when($design === null, fn ($query) => $query->whereNull('user_design_id'))
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        return License::create([
            'user_id' => $user->id,
            'user_design_id' => $design?->id,
            'order_id' => $orderId,
            'type' => $type,
        ]);
    }
}
