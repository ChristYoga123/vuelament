<?php

namespace App\Vuelament\Components\Form;

class Textarea extends BaseForm
{
    protected string $type = 'Textarea';
    protected bool|\Closure $autoResize = false;
    protected int $rows = 4;

    public function autoResize(bool|\Closure $v = true): static { $this->autoResize = $v; return $this; }
    public function rows(int $v): static { $this->rows = $v; return $this; }

    protected function schema(string $operation = 'create'): array
    {
        return [
            'autoResize'  => $this->evaluate($this->autoResize, $operation),
            'rows'        => $this->rows,
        ];
    }
}