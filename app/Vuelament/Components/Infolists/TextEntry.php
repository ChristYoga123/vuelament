<?php

namespace App\Vuelament\Components\Infolists;

class TextEntry extends BaseEntry
{
    protected string $type = 'TextEntry';
    protected ?string $prefix = null;
    protected ?string $suffix = null;
    protected ?string $placeholder = null;
    protected ?int $limit = null;
    protected bool $copyable = false;
    protected bool $html = false;
    protected ?string $size = null;      // sm, md, lg
    protected ?string $weight = null;    // normal, medium, bold

    public function prefix(string $v): static { $this->prefix = $v; return $this; }
    public function suffix(string $v): static { $this->suffix = $v; return $this; }
    public function placeholder(string $v): static { $this->placeholder = $v; return $this; }
    public function limit(int $v): static { $this->limit = $v; return $this; }
    public function copyable(bool $v = true): static { $this->copyable = $v; return $this; }
    public function html(bool $v = true): static { $this->html = $v; return $this; }
    public function size(string $v): static { $this->size = $v; return $this; }
    public function weight(string $v): static { $this->weight = $v; return $this; }

    protected function schema(): array
    {
        return [
            'prefix'      => $this->prefix,
            'suffix'      => $this->suffix,
            'placeholder' => $this->placeholder,
            'limit'       => $this->limit,
            'copyable'    => $this->copyable,
            'html'        => $this->html,
            'size'        => $this->size,
            'weight'      => $this->weight,
        ];
    }
}
