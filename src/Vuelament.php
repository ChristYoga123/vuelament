<?php

namespace ChristYoga123\Vuelament;

use ChristYoga123\Vuelament\Core\Panel;
use Illuminate\Http\Request;

/**
 * Vuelament — Central Panel Registry (Singleton)
 *
 * Kelas ini bertindak sebagai registry pusat untuk semua panel yang terdaftar.
 * Setiap PanelServiceProvider mendaftarkan panel-nya ke registry ini.
 *
 * Resolusi "current panel":
 * - HTTP Request  → Panel dideteksi dari URL path prefix yang cocok
 * - CLI (Console) → Panel dideteksi dari opsi `--panel=`, atau fallback ke default panel
 *
 * Binding di Service Container:
 *   - 'vuelament'       → Vuelament (registry singleton)
 *   - 'vuelament.panel' → Panel (current/active panel, resolved dari registry)
 *
 * Contoh usage:
 *   $registry = app('vuelament');
 *   $panel    = $registry->getCurrentPanel();       // auto-detect
 *   $panel    = $registry->getPanel('admin');        // by ID
 *   $panels   = $registry->getPanels();             // all panels
 *   $panel    = app('vuelament.panel');              // shortcut (sama dengan getCurrentPanel)
 */
class Vuelament
{
    /**
     * Semua panel yang di-register, indexed by panel ID
     *
     * @var array<string, Panel>
     */
    protected array $panels = [];

    /**
     * Panel ID yang sedang aktif (di-set secara eksplisit)
     * Null berarti belum di-set → akan auto-detect
     */
    protected ?string $currentPanelId = null;

    // ── Registration ─────────────────────────────────────

    /**
     * Register sebuah panel ke registry.
     * Dipanggil dari PanelServiceProvider::register()
     */
    public function registerPanel(Panel $panel): void
    {
        $this->panels[$panel->getId()] = $panel;
    }

    // ── Retrieval ────────────────────────────────────────

    /**
     * Dapatkan panel berdasarkan ID.
     *
     * @throws \InvalidArgumentException jika panel ID tidak ditemukan
     */
    public function getPanel(string $id): Panel
    {
        if (!isset($this->panels[$id])) {
            $available = implode(', ', array_keys($this->panels));
            throw new \InvalidArgumentException(
                "Panel [{$id}] is not registered. Available panels: [{$available}]"
            );
        }

        return $this->panels[$id];
    }

    /**
     * Dapatkan semua panel yang terdaftar.
     *
     * @return array<string, Panel>
     */
    public function getPanels(): array
    {
        return $this->panels;
    }

    /**
     * Cek apakah panel dengan ID tertentu terdaftar.
     */
    public function hasPanel(string $id): bool
    {
        return isset($this->panels[$id]);
    }

    /**
     * Dapatkan ID default panel.
     * Priority: config → panel pertama yang di-register
     */
    public function getDefaultPanelId(): string
    {
        $configDefault = config('vuelament.default_panel', 'admin');

        if (isset($this->panels[$configDefault])) {
            return $configDefault;
        }

        // Fallback: panel pertama yang di-register
        return array_key_first($this->panels) ?? 'admin';
    }

    /**
     * Dapatkan default panel instance.
     */
    public function getDefaultPanel(): Panel
    {
        return $this->getPanel($this->getDefaultPanelId());
    }

    // ── Current Panel Resolution ─────────────────────────

    /**
     * Set panel aktif secara eksplisit (untuk CLI atau testing).
     */
    public function setCurrentPanel(string $panelId): void
    {
        if (!$this->hasPanel($panelId)) {
            $available = implode(', ', array_keys($this->panels));
            throw new \InvalidArgumentException(
                "Cannot set current panel to [{$panelId}]. Available panels: [{$available}]"
            );
        }

        $this->currentPanelId = $panelId;
    }

    /**
     * Resolve "current panel" berdasarkan konteks:
     *
     * Priority:
     * 1. Eksplisit di-set via setCurrentPanel() (biasanya dari CLI --panel= option)
     * 2. HTTP Request → detect dari URL path prefix
     * 3. Fallback → default panel dari config
     */
    public function getCurrentPanel(): Panel
    {
        // 1. Jika sudah di-set eksplisit
        if ($this->currentPanelId && isset($this->panels[$this->currentPanelId])) {
            return $this->panels[$this->currentPanelId];
        }

        // 2. HTTP context → detect dari request URL
        if (app()->runningInConsole() === false) {
            $detected = $this->detectPanelFromRequest();
            if ($detected) {
                return $detected;
            }
        }

        // 3. Fallback → default panel
        return $this->getDefaultPanel();
    }

    /**
     * Detect panel dari HTTP request URL path.
     *
     * Membandingkan URL path prefix dengan panel path yang terdaftar.
     * Contoh: Request ke `/admin/users` → match panel dengan path `admin`
     *
     * Menggunakan "longest prefix match" untuk kasus nested path
     * (misal panel `admin` dan `admin-sales`)
     */
    protected function detectPanelFromRequest(): ?Panel
    {
        $request = app(Request::class);
        $path = trim($request->path(), '/');

        // Sort panels by path length descending (longest match first)
        $sortedPanels = $this->panels;
        uasort($sortedPanels, function (Panel $a, Panel $b) {
            return strlen($b->getPath()) <=> strlen($a->getPath());
        });

        foreach ($sortedPanels as $panel) {
            $panelPath = trim($panel->getPath(), '/');

            if ($path === $panelPath || str_starts_with($path, $panelPath . '/')) {
                return $panel;
            }
        }

        return null;
    }

    // ── Helpers ──────────────────────────────────────────

    /**
     * Dapatkan semua panel IDs yang terdaftar.
     *
     * @return string[]
     */
    public function getPanelIds(): array
    {
        return array_keys($this->panels);
    }
}
