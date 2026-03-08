<?php

namespace ChristYoga123\Vuelament\Traits;

use ChristYoga123\Vuelament\Core\Panel;

/**
 * HasPanelAccess — trait for User model
 *
 * Add this trait to the User model to control panel access.
 * Override canAccessPanel() for custom logic.
 *
 * Default: user must have role matching panel ID.
 * Example: panel id 'admin' → user must have role 'admin' or 'super_admin'
 */
trait HasPanelAccess
{
    /**
     * Can the user access this panel?
     *
     * Default logic:
     * - Check if user has role 'super_admin' (can always access all panels)
     * - Or has role matching panel ID
     *
     * Override this method for custom logic.
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

        // If not using spatie/permission, default allow ONLY if in local environment
        return app()->environment('local');
    }
}
