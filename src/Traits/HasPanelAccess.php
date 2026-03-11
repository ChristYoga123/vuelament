<?php

namespace ChristYoga123\Vuelament\Traits;

use ChristYoga123\Vuelament\Core\Panel;
use Illuminate\Support\Facades\Log;

/**
 * HasPanelAccess — trait for User model
 *
 * Add this trait to the User model to control panel access.
 * Override canAccessPanel() for custom logic.
 *
 * Default logic:
 * - super_admin role  → always allowed
 * - role matching panel ID → allowed
 * - no spatie/permission → allowed only in local env (denied in production)
 */
trait HasPanelAccess
{
    /**
     * Can the user access this panel?
     *
     * Override this method in your User model for custom logic.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // Super admin can access all panels
        if (method_exists($this, 'hasRole') && $this->hasRole('super_admin')) {
            return true;
        }

        // Check role matching panel ID (example: panel 'admin' → role 'admin')
        if (method_exists($this, 'hasRole')) {
            return $this->hasRole($panel->getId());
        }

        // [FIX] Without spatie/permission:
        // - DENY in production (secure by default)
        // - Allow in local only for development convenience
        if (app()->environment('local')) {
            Log::warning(
                'Vuelament: spatie/laravel-permission is not installed. ' .
                'Panel access is allowed in [local] environment only. ' .
                'Install spatie/laravel-permission or override canAccessPanel() before deploying to production.'
            );

            return true;
        }

        // [FIX] Deny by default in all non-local environments
        Log::error(
            'Vuelament: Panel access denied — User model uses HasPanelAccess trait but ' .
            'spatie/laravel-permission is not installed. ' .
            'Install spatie/laravel-permission or override canAccessPanel() in your User model.'
        );

        return false;
    }
}
