<?php

namespace App\Vuelament\Components\Actions;

/**
 * ActionGroup — kelompokkan beberapa action dalam satu dropdown
 *
 * Contoh:
 *   ActionGroup::make('Aksi Massal')
 *     ->icon('list')
 *     ->actions([
 *         DeleteBulkAction::make(),
 *         ForceDeleteBulkAction::make(),
 *         RestoreBulkAction::make(),
 *     ])
 */
class ActionGroup
{
    protected string $label;
    protected ?string $icon = null;
    protected ?string $color = null;
    protected array $actions = [];

    public function __construct(string $label = 'Aksi')
    {
        $this->label = $label;
    }

    public static function make(string $label = 'Aksi'): static
    {
        return new static($label);
    }

    public function label(string $v): static { $this->label = $v; return $this; }
    public function icon(string $v): static { $this->icon = $v; return $this; }
    public function color(string $v): static { $this->color = $v; return $this; }

    public function actions(array $v): static
    {
        $this->actions = $v;
        return $this;
    }

    public function toArray(): array
    {
        return [
            'type'    => 'ActionGroup',
            'label'   => $this->label,
            'icon'    => $this->icon,
            'color'   => $this->color,
            'actions' => array_map(fn($a) => $a->toArray(), $this->actions),
        ];
    }
}
