<?php

namespace App\Vuelament\Components\Filters;

class ToggleFilter extends BaseFilter
{
    protected string $type = 'ToggleFilter';
    protected ?string $onLabel = null;
    protected ?string $offLabel = null;

    public function onLabel(string $v): static { $this->onLabel = $v; return $this; }
    public function offLabel(string $v): static { $this->offLabel = $v; return $this; }

    protected function schema(): array
    {
        return [
            'onLabel'  => $this->onLabel,
            'offLabel' => $this->offLabel,
        ];
    }
}
