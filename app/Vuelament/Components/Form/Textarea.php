<?php

namespace App\Vuelament\Components\Form;

class Textarea extends BaseForm
{
    protected string $type = 'Textarea';
    protected string $placeholder = '';
    protected bool $required = false;
    protected bool $disabled = false;
    protected bool $autoResize = false;
    protected int $rows = 4;
    protected ?int $maxLength = null;
    protected ?string $hint = null;

    public function placeholder(string $v): static { $this->placeholder = $v; return $this; }
    public function required(bool $v = true): static { $this->required = $v; return $this; }
    public function disabled(bool $v = true): static { $this->disabled = $v; return $this; }
    public function autoResize(bool $v = true): static { $this->autoResize = $v; return $this; }
    public function rows(int $v): static { $this->rows = $v; return $this; }
    public function maxLength(int $v): static { $this->maxLength = $v; return $this; }
    public function hint(string $v): static { $this->hint = $v; return $this; }

    protected function schema(): array
    {
        return [
            'placeholder' => $this->placeholder,
            'required'    => $this->required,
            'disabled'    => $this->disabled,
            'autoResize'  => $this->autoResize,
            'rows'        => $this->rows,
            'maxLength'   => $this->maxLength,
            'hint'        => $this->hint,
        ];
    }
}