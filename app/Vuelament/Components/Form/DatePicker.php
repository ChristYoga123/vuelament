<?php

namespace App\Vuelament\Components\Form;

class DatePicker extends BaseForm
{
    protected string $type = 'DatePicker';
    protected string $format = 'Y-m-d';
    protected string $displayFormat = 'Y-m-d';
    protected ?string $minDate = null;
    protected ?string $maxDate = null;
    protected ?string $placeholder = null;
    protected bool $required = false;
    protected bool $disabled = false;
    protected bool $readonly = false;

    public function format(string $v): static { $this->format = $v; return $this; }
    public function displayFormat(string $v): static { $this->displayFormat = $v; return $this; }
    public function minDate(string $v): static { $this->minDate = $v; return $this; }
    public function maxDate(string $v): static { $this->maxDate = $v; return $this; }
    public function placeholder(string $v): static { $this->placeholder = $v; return $this; }
    public function required(bool $v = true): static { $this->required = $v; return $this; }
    public function disabled(bool $v = true): static { $this->disabled = $v; return $this; }
    public function readonly(bool $v = true): static { $this->readonly = $v; return $this; }

    protected function schema(): array
    {
        return [
            'format'        => $this->format,
            'displayFormat' => $this->displayFormat,
            'minDate'       => $this->minDate,
            'maxDate'       => $this->maxDate,
            'placeholder'   => $this->placeholder,
            'required'      => $this->required,
            'disabled'      => $this->disabled,
            'readonly'      => $this->readonly,
        ];
    }
}
