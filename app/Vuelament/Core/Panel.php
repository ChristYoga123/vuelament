<?php

namespace App\Vuelament\Core;

/**
 * Panel — konfigurasi panel Vuelament (seperti Filament PanelProvider)
 *
 * Contoh penggunaan di PanelProvider:
 *
 *   Panel::make()
 *     ->id('admin')
 *     ->path('admin')
 *     ->login()
 *     ->register()
 *     // Cara 1: Auto-discover resources dari directory
 *     ->discoverResources(app_path('Vuelament/Resources'), 'App\\Vuelament\\Resources')
 *     // Cara 2: Manual register
 *     ->resources([UserResource::class, RoleResource::class])
 *     // Cara 3: Custom navigation (override auto-navigation)
 *     ->navigation([
 *         NavigationGroup::make('Master Data')->items([
 *             ...UserResource::getNavigationItems(),
 *             NavigationItem::make('Settings')->url('/admin/settings'),
 *         ]),
 *     ])
 *     ->pages([SettingsPage::class])
 *     ->plugins([...])
 */
class Panel
{
    protected string $id = 'admin';
    protected string $path = 'admin';
    protected string $brandName = 'Vuelament';
    protected ?string $brandLogo = null;
    protected bool $hasLogin = true;
    protected bool $hasRegister = false;
    protected bool $hasPasswordReset = false;
    protected ?string $loginUrl = null;
    protected ?string $registerUrl = null;
    protected array $resources = [];
    protected array $pages = [];
    protected array $widgets = [];
    protected array $navigation = [];
    protected array $plugins = [];
    protected array $middleware = ['web'];
    protected array $authMiddleware = [\App\Vuelament\Http\Middleware\Authenticate::class];
    protected array $guestMiddleware = [\App\Vuelament\Http\Middleware\RedirectIfAuthenticated::class];
    protected string $authGuard = 'web';
    protected ?string $userModel = null;
    protected array $colors = [
        'primary' => '#6366f1',
        'success' => '#22c55e',
        'warning' => '#f59e0b',
        'danger'  => '#ef4444',
        'info'    => '#3b82f6',
    ];

    public static function make(): static
    {
        return new static();
    }

    // ── Identity ─────────────────────────────────────────

    public function id(string $v): static { $this->id = $v; return $this; }
    public function path(string $v): static { $this->path = $v; return $this; }
    public function brandName(string $v): static { $this->brandName = $v; return $this; }
    public function brandLogo(string $v): static { $this->brandLogo = $v; return $this; }

    // ── Auth ─────────────────────────────────────────────

    public function login(string $url = null): static { $this->hasLogin = true; $this->loginUrl = $url; return $this; }
    public function register(string $url = null): static { $this->hasRegister = true; $this->registerUrl = $url; return $this; }
    public function passwordReset(bool $v = true): static { $this->hasPasswordReset = $v; return $this; }
    public function authGuard(string $v): static { $this->authGuard = $v; return $this; }
    public function userModel(string $v): static { $this->userModel = $v; return $this; }

    // ── Resources & Pages ────────────────────────────────

    public function resources(array $v): static { $this->resources = array_merge($this->resources, $v); return $this; }
    public function pages(array $v): static { $this->pages = array_merge($this->pages, $v); return $this; }
    public function widgets(array $v): static { $this->widgets = array_merge($this->widgets, $v); return $this; }

    /**
     * Auto-discover resource classes dari directory
     *
     * Contoh: ->discoverResources(app_path('Vuelament/Resources'), 'App\\Vuelament\\Resources')
     */
    public function discoverResources(string $directory, string $namespace): static
    {
        if (!is_dir($directory)) {
            return $this;
        }

        $files = glob($directory . '/*Resource.php');
        foreach ($files as $file) {
            $className = $namespace . '\\' . pathinfo($file, PATHINFO_FILENAME);
            if (class_exists($className) && is_subclass_of($className, BaseResource::class)) {
                $this->resources[] = $className;
            }
        }

        // Deduplicate
        $this->resources = array_unique($this->resources);

        return $this;
    }

    /**
     * Auto-discover custom page classes dari directory
     *
     * Contoh: ->discoverPages(app_path('Vuelament/Pages'), 'App\\Vuelament\\Pages')
     */
    public function discoverPages(string $directory, string $namespace): static
    {
        if (!is_dir($directory)) {
            return $this;
        }

        $files = glob($directory . '/*.php');
        foreach ($files as $file) {
            $className = $namespace . '\\' . pathinfo($file, PATHINFO_FILENAME);
            if (class_exists($className) && is_subclass_of($className, BasePage::class)) {
                $this->pages[] = $className;
            }
        }

        $this->pages = array_unique($this->pages);

        return $this;
    }

    /**
     * Auto-discover widget classes dari directory
     */
    public function discoverWidgets(string $directory, string $namespace): static
    {
        if (!is_dir($directory)) {
            return $this;
        }

        $files = glob($directory . '/*.php');
        foreach ($files as $file) {
            $className = $namespace . '\\' . pathinfo($file, PATHINFO_FILENAME);
            if (class_exists($className) && is_subclass_of($className, \App\Vuelament\Components\Widgets\BaseWidget::class)) {
                $this->widgets[] = $className;
            }
        }

        $this->widgets = array_unique($this->widgets);

        return $this;
    }

    // ── Navigation ───────────────────────────────────────

    /**
     * Set custom navigation. Kalau di-set, ini OVERRIDE auto-navigation dari resources.
     *
     * Bisa mix NavigationGroup, NavigationItem, dan spread Resource::getNavigationItems()
     *
     * Contoh:
     *   ->navigation([
     *       NavigationGroup::make('Master')
     *           ->icon('database')
     *           ->items([
     *               ...UserResource::getNavigationItems(),
     *               ...RoleResource::getNavigationItems(),
     *           ]),
     *       NavigationGroup::make('Pengaturan')
     *           ->items([
     *               NavigationItem::make('Settings')->url('/admin/settings'),
     *               NavigationItem::make('Laporan')->route('admin.reports.index'),
     *           ]),
     *   ])
     */
    public function navigation(array $v): static { $this->navigation = $v; return $this; }

    // ── Plugins ──────────────────────────────────────────

    public function plugins(array $v): static { $this->plugins = $v; return $this; }

    // ── Middleware ────────────────────────────────────────

    public function middleware(array $v): static { $this->middleware = $v; return $this; }
    public function authMiddleware(array $v): static { $this->authMiddleware = $v; return $this; }
    public function guestMiddleware(array $v): static { $this->guestMiddleware = $v; return $this; }

    // ── Colors ───────────────────────────────────────────

    public function colors(array $v): static { $this->colors = array_merge($this->colors, $v); return $this; }

    // ── Boot (register plugins) ──────────────────────────

    public function boot(): void
    {
        foreach ($this->plugins as $plugin) {
            if (method_exists($plugin, 'boot')) {
                $plugin->boot($this);
            }
        }
    }

    // ── Build navigation ─────────────────────────────────
    //
    // Priority:
    // 1. Jika navigation() di-set manual → pake itu
    // 2. Jika tidak → auto-build dari resources (grouped + sorted)

    public function buildNavigation(): array
    {
        if (!empty($this->navigation)) {
            return $this->buildCustomNavigation();
        }

        return $this->buildAutoNavigation();
    }

    /**
     * Build navigation dari custom navigation() array
     * Supports NavigationGroup dan NavigationItem di top-level
     */
    protected function buildCustomNavigation(): array
    {
        $result = [];

        foreach ($this->navigation as $item) {
            if ($item instanceof NavigationGroup) {
                $groupArray = $item->toArray();
                // Prefix relative URLs with panel path
                $groupArray['items'] = array_map(
                    fn($i) => $this->prefixItemUrl($i),
                    $groupArray['items']
                );
                $result[] = $groupArray;
            } elseif ($item instanceof NavigationItem) {
                // Top-level item tanpa group
                $itemArray = $item->toArray();
                $itemArray = $this->prefixItemUrl($itemArray);
                $result[] = [
                    'label'       => null,
                    'icon'        => null,
                    'collapsible' => false,
                    'collapsed'   => false,
                    'sort'        => $item->getSort(),
                    'items'       => [$itemArray],
                ];
            }
        }

        return $result;
    }

    /**
     * Prefix relative URL with panel path
     * URL absolute (mulai / atau http) dibiarkan
     * URL relative (misal 'users') jadi '/admin/users'
     */
    protected function prefixItemUrl(array $item): array
    {
        $url = $item['url'] ?? null;
        if ($url && !str_starts_with($url, '/') && !str_starts_with($url, 'http')) {
            $item['url'] = '/' . $this->path . '/' . $url;
        }
        return $item;
    }

    /**
     * Auto-build navigation dari registered resources
     * Group by $navigationGroup, sort by $navigationSort
     */
    protected function buildAutoNavigation(): array
    {
        $groups = [];

        foreach ($this->resources as $resource) {
            $groupName = $resource::getNavigationGroup();
            $key       = $groupName ?? '__ungrouped__';

            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'label'       => $groupName,
                    'icon'        => null,
                    'collapsible' => $groupName !== null,
                    'collapsed'   => false,
                    'sort'        => PHP_INT_MAX,
                    'items'       => [],
                ];
            }

            $groups[$key]['items'][] = [
                'label'      => $resource::getNavigationLabel(),
                'icon'       => $resource::getNavigationIcon(),
                'url'        => '/' . $this->path . '/' . $resource::getSlug(),
                'badge'      => null,
                'badgeColor' => null,
                'sort'       => $resource::getNavigationSort(),
            ];

            // Group sort = minimum sort dari items-nya
            $groups[$key]['sort'] = min($groups[$key]['sort'], $resource::getNavigationSort());
        }

        // Sort items within each group
        foreach ($groups as &$group) {
            usort($group['items'], fn($a, $b) => $a['sort'] <=> $b['sort']);
        }

        // Sort groups
        $result = array_values($groups);
        usort($result, fn($a, $b) => $a['sort'] <=> $b['sort']);

        return $result;
    }

    // ── Getters ──────────────────────────────────────────

    public function getId(): string { return $this->id; }
    public function getPath(): string { return $this->path; }
    public function getBrandName(): string { return $this->brandName; }
    public function getBrandLogo(): ?string { return $this->brandLogo; }
    public function hasLogin(): bool { return $this->hasLogin; }
    public function hasRegister(): bool { return $this->hasRegister; }
    public function hasPasswordReset(): bool { return $this->hasPasswordReset; }
    public function getLoginUrl(): string { return $this->loginUrl ?? '/' . $this->path . '/login'; }
    public function getRegisterUrl(): string { return $this->registerUrl ?? '/' . $this->path . '/register'; }
    public function getResources(): array { return $this->resources; }
    public function getPages(): array { return $this->pages; }
    public function getWidgets(): array { return $this->widgets; }
    public function getNavigation(): array { return $this->navigation; }
    public function getPlugins(): array { return $this->plugins; }
    public function getMiddleware(): array { return $this->middleware; }
    public function getAuthMiddleware(): array { return $this->authMiddleware; }
    public function getGuestMiddleware(): array { return $this->guestMiddleware; }
    public function getAuthGuard(): string { return $this->authGuard; }
    public function getUserModel(): ?string { return $this->userModel; }
    public function getColors(): array { return $this->colors; }

    // ── Share ke Inertia (sebagai shared props) ──────────

    public function toArray(string $operation = 'create'): array
    {
        return [
            'id'            => $this->id,
            'path'          => $this->path,
            'brandName'     => $this->brandName,
            'brandLogo'     => $this->brandLogo,
            'hasLogin'      => $this->hasLogin,
            'hasRegister'   => $this->hasRegister,
            'colors'        => $this->colors,
            'navigation'    => $this->buildNavigation(),
        ];
    }
}
