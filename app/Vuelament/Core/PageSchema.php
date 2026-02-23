<?php

namespace App\Vuelament\Core;

class PageSchema
{
    protected string $title = '';
    protected string $description = '';
    protected array $components = [];

    public static function make(): static
    {
        return new static();
    }

    public function title(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function description(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function components(array $components): static
    {
        $this->components = $components;
        return $this;
    }

    public function getComponents(): array { return $this->components; }

    public function toArray(string $operation = 'create'): array
    {
        return [
            'title'       => $this->title,
            'description' => $this->description,
            'components'  => array_map(fn($c) => $c->toArray($operation), $this->components),
        ];
    }
}