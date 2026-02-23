<?php

namespace App\Vuelament\Components\Form;

class TextInput extends BaseForm
{
    protected string $type = 'TextInput';
    protected string $inputType = 'text';
    protected string $placeholder = '';
    protected bool|\Closure $required = false;
    protected bool $disabled = false;
    protected bool $readonly = false;
    protected ?int $minLength = null;
    protected ?int $maxLength = null;
    protected ?string $hint = null;
    protected ?string $prefix = null;
    protected ?string $prefixIcon = null;
    protected ?string $suffix = null;
    protected ?string $suffixIcon = null;
    protected bool $revealable = false;

    public function type(string $v): static { $this->inputType = $v; return $this; }
    public function email(): static { return $this->type('email'); }
    public function password(): static { return $this->type('password'); }
    public function number(): static { return $this->type('number'); }
    public function placeholder(string $v): static { $this->placeholder = $v; return $this; }
    public function required(bool|\Closure $v = true): static { $this->required = $v; return $this; }
    public function getRequiredProp(): bool|\Closure { return $this->required; }
    public function disabled(bool $v = true): static { $this->disabled = $v; return $this; }
    public function readonly(bool $v = true): static { $this->readonly = $v; return $this; }
    public function minLength(int $v): static { $this->minLength = $v; return $this; }
    public function maxLength(int $v): static { $this->maxLength = $v; return $this; }
    public function hint(string $v): static { $this->hint = $v; return $this; }
    public function prefix(string $v): static { $this->prefix = $v; return $this; }
    public function prefixIcon(string $v): static { $this->prefixIcon = $v; return $this; }
    public function suffix(string $v): static { $this->suffix = $v; return $this; }
    public function suffixIcon(string $v): static { $this->suffixIcon = $v; return $this; }
    public function revealable(bool $v = true): static { $this->revealable = $v; return $this; }

    protected function schema(string $operation = 'create'): array
    {
        $isRequired = $this->required;
        if (is_callable($isRequired)) {
            $isRequired = app()->call($isRequired, ['operation' => $operation]);
        }

        return [
            'inputType'   => $this->inputType,
            'placeholder' => $this->placeholder,
            'required'    => $isRequired,
            'disabled'    => $this->disabled,
            'readonly'    => $this->readonly,
            'minLength'   => $this->minLength,
            'maxLength'   => $this->maxLength,
            'hint'        => $this->hint,
            'prefix'      => $this->prefix,
            'prefixIcon'  => $this->prefixIcon,
            'suffix'      => $this->suffix,
            'suffixIcon'  => $this->suffixIcon,
            'revealable'  => $this->revealable,
        ];
    }
}