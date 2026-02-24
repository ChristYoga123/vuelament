<?php

namespace App\Vuelament\Components\Filters;

class SelectFilter extends BaseFilter
{
    protected string $type = 'SelectFilter';
    protected array $options = [];
    protected bool $searchable = false;
    protected bool $multiple = false;
    protected ?string $optionsFrom = null; // load dari endpoint
    protected bool $isTrashed = false;

    public function options(array $options): static
    {
        $result = [];
        foreach ($options as $key => $val) {
            if (is_array($val)) {
                $result[] = $val;
            } elseif (is_int($key)) {
                $result[] = ['value' => $val, 'label' => ucfirst($val)];
            } else {
                $result[] = ['value' => $key, 'label' => $val];
            }
        }
        $this->options = $result;
        return $this;
    }

    public function optionsFrom(string $endpoint): static { $this->optionsFrom = $endpoint; return $this; }
    public function searchable(bool $v = true): static { $this->searchable = $v; return $this; }
    public function multiple(bool $v = true): static { $this->multiple = $v; return $this; }

    public function withTrashed(): static
    {
        $this->isTrashed = true;
        if (empty($this->name) || $this->name === 'default') {
            $this->name = 'trashed';
        }
        return $this->label('Status Terhapus')
                    ->placeholder('Tidak Termasuk Dihapus')
                    ->options([
                        'with' => 'Termasuk Dihapus',
                        'only' => 'Hanya yang Dihapus',
                    ]);
    }

    protected function schema(): array
    {
        return [
            'options'     => $this->options,
            'optionsFrom' => $this->optionsFrom,
            'searchable'  => $this->searchable,
            'multiple'    => $this->multiple,
            'isTrashed'   => $this->isTrashed,
        ];
    }
}