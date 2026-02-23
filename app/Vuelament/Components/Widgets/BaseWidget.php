<?php

namespace App\Vuelament\Components\Widgets;

abstract class BaseWidget
{
    protected string $type = '';
    protected ?string $heading = null;
    protected ?string $description = null;
    protected int $columnSpan = 1;
    protected int $sort = 0;

    public static function make(): static
    {
        return new static();
    }

    public function heading(string $v): static { $this->heading = $v; return $this; }
    public function description(string $v): static { $this->description = $v; return $this; }
    public function columnSpan(int $v): static { $this->columnSpan = $v; return $this; }
    public function sort(int $v): static { $this->sort = $v; return $this; }

    public function getSort(): int { return $this->sort; }

    abstract public function toArray(string $operation = 'create'): array;
}
