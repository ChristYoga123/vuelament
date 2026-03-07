<?php

namespace ChristYoga123\Vuelament\Components\Form;

class Form
{
    protected array $schema = [];

    public static function make(): static
    {
        return new static();
    }

    public function schema(array $components): static
    {
        $this->schema = $components;
        return $this;
    }

    public function getComponents(): array
    {
        return $this->schema;
    }

    public function toArray(string $operation = 'create'): array
    {
        return array_map(fn($c) => $c->toArray($operation), $this->schema);
    }
}
