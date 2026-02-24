<?php

namespace App\Vuelament\Components\Form;

class DatePicker extends BaseForm
{
    protected string $type = 'DatePicker';
    protected string $format = 'Y-m-d';
    protected string $displayFormat = 'Y-m-d';
    protected ?string $minDate = null;
    protected ?string $maxDate = null;

    public function format(string $v): static { $this->format = $v; return $this; }
    public function displayFormat(string $v): static { $this->displayFormat = $v; return $this; }
    public function minDate(string $v): static { $this->minDate = $v; return $this; }
    public function maxDate(string $v): static { $this->maxDate = $v; return $this; }

    protected function schema(string $operation = 'create'): array
    {
        return [
            'format'        => $this->format,
            'displayFormat' => $this->displayFormat,
            'minDate'       => $this->minDate,
            'maxDate'       => $this->maxDate,
        ];
    }
}
