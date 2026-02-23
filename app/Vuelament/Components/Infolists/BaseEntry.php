<?php

namespace App\Vuelament\Components\Infolists;

use App\Vuelament\Core\BaseComponent;

abstract class BaseEntry extends BaseComponent
{
    protected ?string $icon = null;
    protected ?string $tooltip = null;
    protected ?string $color = null;
    protected ?string $url = null;
    protected bool $openUrlInNewTab = false;
    protected int $columnSpan = 1;

    public function icon(string $v): static { $this->icon = $v; return $this; }
    public function tooltip(string $v): static { $this->tooltip = $v; return $this; }
    public function color(string $v): static { $this->color = $v; return $this; }
    public function url(string $v, bool $openInNewTab = false): static { $this->url = $v; $this->openUrlInNewTab = $openInNewTab; return $this; }
    public function columnSpan(int $v): static { $this->columnSpan = $v; return $this; }

    public function toArray(string $operation = 'create'): array
    {
        return array_merge(parent::toArray(), [
            'icon'            => $this->icon,
            'tooltip'         => $this->tooltip,
            'color'           => $this->color,
            'url'             => $this->url,
            'openUrlInNewTab' => $this->openUrlInNewTab,
            'columnSpan'      => $this->columnSpan,
        ]);
    }
}
