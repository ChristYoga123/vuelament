<?php

namespace App\Vuelament\Components\Form;

class TextInput extends BaseForm
{
    protected string $type = 'TextInput';
    protected string $inputType = 'text';
    protected ?string $prefix = null;
    protected ?string $prefixIcon = null;
    protected ?string $suffix = null;
    protected ?string $suffixIcon = null;
    protected bool|\Closure $revealable = false;

    public function type(string $v): static { $this->inputType = $v; return $this; }
    public function email(): static { return $this->type('email'); }
    public function password(): static { return $this->type('password'); }
    public function number(): static { return $this->type('number'); }
    public function prefix(string $v): static { $this->prefix = $v; return $this; }
    public function prefixIcon(string $v): static { $this->prefixIcon = $v; return $this; }
    public function suffix(string $v): static { $this->suffix = $v; return $this; }
    public function suffixIcon(string $v): static { $this->suffixIcon = $v; return $this; }
    public function revealable(bool|\Closure $v = true): static { $this->revealable = $v; return $this; }

    protected function schema(string $operation = 'create'): array
    {
        return [
            'inputType'   => $this->inputType,
            'prefix'      => $this->prefix,
            'prefixIcon'  => $this->prefixIcon,
            'suffix'      => $this->suffix,
            'suffixIcon'  => $this->suffixIcon,
            'revealable'  => $this->evaluate($this->revealable, $operation),
        ];
    }
}