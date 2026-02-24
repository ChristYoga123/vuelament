<?php

namespace App\Vuelament\Components\Form;

class Toggle extends BaseForm
{
    protected string $type = 'Toggle';
    protected ?string $onLabel = null;
    protected ?string $offLabel = null;
    protected ?string $onColor = null;
    protected ?string $offColor = null;

    public function onLabel(string $v): static { $this->onLabel = $v; return $this; }
    public function offLabel(string $v): static { $this->offLabel = $v; return $this; }
    public function onColor(string $v): static { $this->onColor = $v; return $this; }
    public function offColor(string $v): static { $this->offColor = $v; return $this; }

    protected function schema(string $operation = 'create'): array
    {
        return [
            'onLabel'  => $this->onLabel,
            'offLabel' => $this->offLabel,
            'onColor'  => $this->onColor,
            'offColor' => $this->offColor,
        ];
    }
}
