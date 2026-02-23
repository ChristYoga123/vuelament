<?php

namespace App\Vuelament\Components\Layout;

class Card
{
    protected string $type = 'Card';
    protected ?string $heading = null;
    protected ?string $description = null;
    protected array $childComponents = [];

    public function __construct(string $heading = '')
    {
        $this->heading = $heading ?: null;
    }

    public static function make(string $heading = ''): static
    {
        return new static($heading);
    }

    public function heading(string $v): static { $this->heading = $v; return $this; }
    public function description(string $v): static { $this->description = $v; return $this; }

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
            'components'  => array_map(fn($c) => $c->toArray($operation), $this->childComponents),
        ];
    }
}
