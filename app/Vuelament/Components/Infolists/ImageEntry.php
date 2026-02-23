<?php

namespace App\Vuelament\Components\Infolists;

class ImageEntry extends BaseEntry
{
    protected string $type = 'ImageEntry';
    protected ?int $width = null;
    protected ?int $height = null;
    protected bool $circular = false;
    protected bool $square = false;
    protected ?string $defaultImageUrl = null;

    public function width(int $v): static { $this->width = $v; return $this; }
    public function height(int $v): static { $this->height = $v; return $this; }
    public function size(int $v): static { $this->width = $v; $this->height = $v; return $this; }
    public function circular(bool $v = true): static { $this->circular = $v; return $this; }
    public function square(bool $v = true): static { $this->square = $v; return $this; }
    public function defaultImageUrl(string $v): static { $this->defaultImageUrl = $v; return $this; }

    protected function schema(): array
    {
        return [
            'width'           => $this->width,
            'height'          => $this->height,
            'circular'        => $this->circular,
            'square'          => $this->square,
            'defaultImageUrl' => $this->defaultImageUrl,
        ];
    }
}
