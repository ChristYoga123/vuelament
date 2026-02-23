<?php

namespace App\Vuelament\Components\Form;

class Toggle extends BaseForm
{
    protected string $type = 'Toggle';
    protected bool $required = false;
    protected bool $disabled = false;
    protected ?string $hint = null;
    protected ?string $onLabel = null;
    protected ?string $offLabel = null;
    protected ?string $onColor = null;
    protected ?string $offColor = null;

    public function required(bool $v = true): static { $this->required = $v; return $this; }
    public function disabled(bool $v = true): static { $this->disabled = $v; return $this; }
    public function hint(string $v): static { $this->hint = $v; return $this; }
    public function onLabel(string $v): static { $this->onLabel = $v; return $this; }
    public function offLabel(string $v): static { $this->offLabel = $v; return $this; }
    public function onColor(string $v): static { $this->onColor = $v; return $this; }
    public function offColor(string $v): static { $this->offColor = $v; return $this; }

    protected function schema(): array
    {
        return [
            'required' => $this->required,
            'disabled' => $this->disabled,
            'hint'     => $this->hint,
            'onLabel'  => $this->onLabel,
            'offLabel' => $this->offLabel,
            'onColor'  => $this->onColor,
            'offColor' => $this->offColor,
        ];
    }
}
