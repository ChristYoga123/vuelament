<?php

namespace App\Vuelament\Components\Table;

class Column
{
    protected string $name = '';
    protected string $type = 'TextColumn';
    protected string $label = '';
    protected bool $sortable = false;
    protected bool $searchable = false;
    protected bool $toggleable = false;
    protected bool $hidden = false;
    protected ?string $alignment = null;   // left, center, right
    protected ?string $width = null;
    protected ?string $color = null;
    protected ?string $icon = null;
    protected ?string $prefix = null;
    protected ?string $suffix = null;
    protected bool $html = false;
    protected bool $copyable = false;
    protected ?int $limit = null;
    protected ?string $placeholder = null;
    protected ?string $dateFormat = null;
    protected ?string $moneyFormat = null;
    protected bool $badge = false;
    protected bool $isToggle = false;
    protected ?array $badgeColors = null;   // ['draft' => 'warning', 'published' => 'success']

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public static function make(string $name): static
    {
        return new static($name);
    }

    public function label(string $v): static { $this->label = $v; return $this; }
    public function sortable(bool $v = true): static { $this->sortable = $v; return $this; }
    public function searchable(bool $v = true): static { $this->searchable = $v; return $this; }
    public function toggleable(bool $v = true, bool $isToggledHiddenByDefault = false): static { $this->toggleable = $v; $this->hidden = $isToggledHiddenByDefault; return $this; }
    public function hidden(bool $v = true): static { $this->hidden = $v; return $this; }
    public function alignment(string $v): static { $this->alignment = $v; return $this; }
    public function alignCenter(): static { return $this->alignment('center'); }
    public function alignRight(): static { return $this->alignment('right'); }
    public function width(string $v): static { $this->width = $v; return $this; }
    public function color(string $v): static { $this->color = $v; return $this; }
    public function icon(string $v): static { $this->icon = $v; return $this; }
    public function prefix(string $v): static { $this->prefix = $v; return $this; }
    public function suffix(string $v): static { $this->suffix = $v; return $this; }
    public function html(bool $v = true): static { $this->html = $v; return $this; }
    public function copyable(bool $v = true): static { $this->copyable = $v; return $this; }
    public function limit(int $v): static { $this->limit = $v; return $this; }
    public function placeholder(string $v): static { $this->placeholder = $v; return $this; }
    public function dateFormat(string $v): static { $this->dateFormat = $v; return $this; }
    public function money(string $format = 'IDR'): static { $this->moneyFormat = $format; return $this; }
    public function badge(array $colors = []): static { $this->badge = true; $this->badgeColors = $colors; return $this; }
    public function asToggle(bool $v = true): static { $this->isToggle = $v; return $this; }

    public function toArray(string $operation = 'create'): array
    {
        return [
            'type'        => $this->type ?? class_basename(static::class),
            'name'        => $this->name,
            'label'       => $this->label,
            'sortable'    => $this->sortable,
            'searchable'  => $this->searchable,
            'toggleable'  => $this->toggleable,
            'hidden'      => $this->hidden,
            'alignment'   => $this->alignment,
            'width'       => $this->width,
            'color'       => $this->color,
            'icon'        => $this->icon,
            'prefix'      => $this->prefix,
            'suffix'      => $this->suffix,
            'html'        => $this->html,
            'copyable'    => $this->copyable,
            'limit'       => $this->limit,
            'placeholder' => $this->placeholder,
            'dateFormat'  => $this->dateFormat,
            'moneyFormat' => $this->moneyFormat,
            'badge'       => $this->badge,
            'badgeColors' => $this->badgeColors,
            'isToggle'    => $this->isToggle,
        ];
    }
}
