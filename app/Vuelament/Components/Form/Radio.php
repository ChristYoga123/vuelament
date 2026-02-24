<?php

namespace App\Vuelament\Components\Form;

class Radio extends BaseForm
{
    protected string $type = 'Radio';
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

    protected function schema(string $operation = 'create'): array
    {
        return [
            'options'  => $this->options,
            'layout'   => $this->layout,
        ];
    }
}