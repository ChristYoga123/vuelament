<?php

namespace App\Vuelament\Components\Form;

class Checkbox extends BaseForm
{
    protected string $type = 'Checkbox';
    protected array $options = [];
    protected string $layout = 'vertical';
    protected bool|\Closure $multiple = false;

    public function options(array $options): static
    {
        $this->multiple = true;
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

    public function single(): static { $this->multiple = false; return $this; }
    public function inline(): static { $this->layout = 'horizontal'; return $this; }

    protected function schema(string $operation = 'create'): array
    {
        return [
            'multiple' => $this->evaluate($this->multiple, $operation),
            'options'  => $this->options,
            'layout'   => $this->layout,
        ];
    }
}