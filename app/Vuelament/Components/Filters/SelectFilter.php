<?php

namespace App\Vuelament\Components\Filters;

class SelectFilter extends BaseFilter
{
    protected string $type = 'SelectFilter';
    protected array $options = [];
    protected bool $searchable = false;
    protected bool $multiple = false;
    protected ?string $optionsFrom = null; // load dari endpoint

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

    protected function schema(): array
    {
        return [
            'options'     => $this->options,
            'optionsFrom' => $this->optionsFrom,
            'searchable'  => $this->searchable,
            'multiple'    => $this->multiple,
        ];
    }
}