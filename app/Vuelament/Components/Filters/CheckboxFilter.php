<?php

namespace App\Vuelament\Components\Filters;

class CheckboxFilter extends BaseFilter
{
    protected string $type = 'CheckboxFilter';
    protected array $options = [];
    protected string $layout = 'vertical';

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

    public function inline(): static { $this->layout = 'horizontal'; return $this; }

    protected function schema(): array
    {
        return [
            'options' => $this->options,
            'layout'  => $this->layout,
        ];
    }
}
