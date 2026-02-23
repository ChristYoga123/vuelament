<?php

namespace App\Vuelament\Traits;

use App\Vuelament\Core\Panel;

/**
 * HasPanelAccess — trait untuk User model
 *
 * Tambahkan trait ini ke model User untuk mengontrol akses ke panel.
 * Override canAccessPanel() untuk custom logic.
 *
 * Default: user harus punya role yang sama dengan panel ID.
 * Contoh: panel id 'admin' → user harus punya role 'admin' atau 'super_admin'
 */
trait HasPanelAccess
{
    /**
     * Apakah user bisa akses panel ini?
     *
     * Default logic:
     * - Cek apakah user punya role 'super_admin' (selalu bisa akses semua panel)
     * - Atau punya role yang sama dengan panel ID
     *
     * Override method ini untuk custom logic.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // Super admin bisa akses semua panel
        if (method_exists($this, 'hasRole') && $this->hasRole('super_admin')) {
            return true;
        }

        // Cek role sesuai panel ID (contoh: panel 'admin' → role 'admin')
        if (method_exists($this, 'hasRole')) {
            return $this->hasRole($panel->getId());
        }

        // Kalau tidak pakai spatie/permission, default allow
        return true;
    }
}
