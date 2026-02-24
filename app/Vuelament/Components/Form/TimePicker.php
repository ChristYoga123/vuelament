<?php

namespace App\Vuelament\Components\Form;

class TimePicker extends BaseForm
{
    protected string $type = 'TimePicker';
    protected string $format = 'H:i:s';
    protected string $displayFormat = 'H:i';
    protected int $step = 60;
    protected ?string $minTime = null;
    protected ?string $maxTime = null;

    public function format(string $v): static { $this->format = $v; return $this; }
    public function displayFormat(string $v): static { $this->displayFormat = $v; return $this; }
    public function step(int $v): static { $this->step = $v; return $this; }
    public function minTime(string $v): static { $this->minTime = $v; return $this; }
    public function maxTime(string $v): static { $this->maxTime = $v; return $this; }

    protected function schema(string $operation = 'create'): array
    {
        return [
            'format'        => $this->format,
            'displayFormat' => $this->displayFormat,
            'step'          => $this->step,
            'minTime'       => $this->minTime,
            'maxTime'       => $this->maxTime,
        ];
    }
}
