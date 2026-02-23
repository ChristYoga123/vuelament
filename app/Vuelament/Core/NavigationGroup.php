<?php

namespace App\Vuelament\Core;

/**
 * NavigationGroup — kelompokkan menu navigasi
 *
 * Contoh:
 *   NavigationGroup::make('Master Data')
 *     ->icon('database')
 *     ->items([
 *         NavigationItem::make('Users')->url('/admin/users')->icon('users'),
 *     ])
 */
class NavigationGroup
{
    protected string $label = '';
    protected ?string $icon = null;
    protected array $items = [];
    protected bool $collapsible = true;
    protected bool $collapsed = false;
    protected int $sort = 0;

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
    public function collapsible(bool $v = true): static { $this->collapsible = $v; return $this; }
    public function collapsed(bool $v = true): static { $this->collapsible = true; $this->collapsed = $v; return $this; }
    public function sort(int $v): static { $this->sort = $v; return $this; }

    public function items(array $items): static
    {
        $this->items = $items;
        return $this;
    }

    public function toArray(string $operation = 'create'): array
    {
        return [
            'label'       => $this->label,
            'icon'        => $this->icon,
            'collapsible' => $this->collapsible,
            'collapsed'   => $this->collapsed,
            'sort'        => $this->sort,
            'items'       => array_map(fn($i) => $i->toArray(), $this->items),
        ];
    }
}
