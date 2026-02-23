<?php

namespace App\Vuelament\Components\Layout;

class Grid
{
    protected string $type = 'Grid';
    protected int $columns = 2;
    protected array $childComponents = [];

    public function __construct(int $columns = 2)
    {
        $this->columns = $columns;
    }

    public static function make(string|int $columns = 2): static
    {
        return new static((int) $columns);
    }

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
            'type'       => $this->type,
            'columns'    => $this->columns,
            'components' => array_map(fn($c) => $c->toArray($operation), $this->childComponents),
        ];
    }
}
