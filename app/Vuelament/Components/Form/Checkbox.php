<?php

namespace App\Vuelament\Components\Form;

class Checkbox extends BaseForm
{
    protected string $type = 'Checkbox';
    protected array $options = [];
    protected bool $required = false;
    protected bool $disabled = false;
    protected string $layout = 'vertical';
    protected ?string $hint = null;
    protected bool $multiple = false;

    /**
     * Multiple checkboxes — pilih beberapa dari daftar opsi
     *
     * contoh:
     *   V::checkbox('permissions')
     *     ->label('Permissions')
     *     ->options(['read' => 'Can Read', 'write' => 'Can Write'])
     */
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
    public function required(bool $v = true): static { $this->required = $v; return $this; }
    public function disabled(bool $v = true): static { $this->disabled = $v; return $this; }
    public function inline(): static { $this->layout = 'horizontal'; return $this; }
    public function hint(string $v): static { $this->hint = $v; return $this; }

    protected function schema(): array
    {
        return [
            'multiple' => $this->multiple,
            'options'  => $this->options,
            'required' => $this->required,
            'disabled' => $this->disabled,
            'layout'   => $this->layout,
            'hint'     => $this->hint,
        ];
    }
}