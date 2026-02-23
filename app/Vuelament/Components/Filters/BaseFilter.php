<?php

namespace App\Vuelament\Components\Filters;

abstract class BaseFilter
{
    protected string $type = '';
    protected string $name = '';
    protected string $label = '';
    protected mixed $default = null;
    protected ?string $placeholder = null;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public static function make(string $name): static
    {
        return new static($name);
    }

    public function label(string $label): static { $this->label = $label; return $this; }
    public function default(mixed $default): static { $this->default = $default; return $this; }
    public function placeholder(string $v): static { $this->placeholder = $v; return $this; }

    public function toArray(): array
    {
        return array_merge([
            'type'        => $this->type,
            'name'        => $this->name,
            'label'       => $this->label,
            'default'     => $this->default,
            'placeholder' => $this->placeholder,
        ], $this->schema());
    }

    protected function schema(): array
    {
        return [];
    }
}