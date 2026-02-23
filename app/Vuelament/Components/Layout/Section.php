<?php

namespace App\Vuelament\Components\Layout;

class Section
{
    protected string $type = 'Section';
    protected string $heading = '';
    protected ?string $description = null;
    protected ?string $icon = null;
    protected bool $collapsible = false;
    protected bool $collapsed = false;
    protected ?int $columns = null;
    protected array $childComponents = [];

    public function __construct(string $heading = '')
    {
        $this->heading = $heading;
    }

    public static function make(string $heading = ''): static
    {
        return new static($heading);
    }

    public function heading(string $v): static { $this->heading = $v; return $this; }
    public function description(string $v): static { $this->description = $v; return $this; }
    public function icon(string $v): static { $this->icon = $v; return $this; }
    public function collapsible(bool $v = true): static { $this->collapsible = $v; return $this; }
    public function collapsed(bool $v = true): static { $this->collapsible = true; $this->collapsed = $v; return $this; }
    public function columns(int $v): static { $this->columns = $v; return $this; }

    public function schema(array $components): static
    {
        $this->childComponents = $components;
        return $this;
    }

    public function getComponents(): array { return $this->childComponents; }

    public function toArray(string $operation = 'create'): array
    {
        return [
            'type'        => $this->type,
            'heading'     => $this->heading,
            'description' => $this->description,
            'icon'        => $this->icon,
            'collapsible' => $this->collapsible,
            'collapsed'   => $this->collapsed,
            'columns'     => $this->columns,
            'components'  => array_map(fn($c) => $c->toArray($operation), $this->childComponents),
        ];
    }
}
