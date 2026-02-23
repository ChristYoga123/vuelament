<?php

namespace App\Vuelament\Core;

class NavigationItem
{
    protected string $label = '';
    protected ?string $icon = null;
    protected ?string $url = null;
    protected ?string $route = null;
    protected array $routeParams = [];
    protected ?string $badge = null;
    protected ?string $badgeColor = null;
    protected int $sort = 0;
    protected bool $isActive = false;

    public function __construct(string $label = '')
    {
        $this->label = $label;
    }

    public static function make(string $label = ''): static
    {
        return new static($label);
    }

    public function label(string $v): static { $this->label = $v; return $this; }
    public function icon(string $v): static { $this->icon = $v; return $this; }
    public function sort(int $v): static { $this->sort = $v; return $this; }

    /**
     * Set URL langsung (absolute path)
     */
    public function url(string $v): static { $this->url = $v; return $this; }

    /**
     * Set route name + params
     *
     * Contoh: ->route('admin.users.index')
     */
    public function route(string $name, array $params = []): static
    {
        $this->route = $name;
        $this->routeParams = $params;
        return $this;
    }

    public function badge(?string $v, string $color = 'primary'): static
    {
        $this->badge = $v;
        $this->badgeColor = $color;
        return $this;
    }

    public function isActive(bool $v = true): static { $this->isActive = $v; return $this; }

    public function getSort(): int { return $this->sort; }

    public function resolveUrl(): ?string
    {
        if ($this->url) {
            return $this->url;
        }

        if ($this->route) {
            try {
                return route($this->route, $this->routeParams);
            } catch (\Exception $e) {
                return null;
            }
        }

        return null;
    }

    public function toArray(string $operation = 'create'): array
    {
        return [
            'label'      => $this->label,
            'icon'       => $this->icon,
            'url'        => $this->resolveUrl(),
            'badge'      => $this->badge,
            'badgeColor' => $this->badgeColor,
            'sort'       => $this->sort,
        ];
    }
}
