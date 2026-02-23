<?php

namespace App\Vuelament\Core;

abstract class BaseComponent
{
    protected string $type = '';
    protected string $name = '';
    protected string $label = '';
    protected bool|\Closure $hidden = false;
    protected array $conditions = [];

    public function __construct(string $name = '')
    {
        $this->name = $name;
    }

    public static function make(string $name = ''): static
    {
        return new static($name);
    }

    public function label(string $label): static { $this->label = $label; return $this; }
    public function hidden(bool|\Closure $v = true): static { $this->hidden = $v; return $this; }

    // Tampilkan komponen hanya jika field lain bernilai tertentu
    // contoh: ->showWhen('role', 'admin')
    public function showWhen(string $field, mixed $value): static
    {
        $this->conditions[] = [
            'field'    => $field,
            'value'    => $value,
            'operator' => '=',
        ];
        return $this;
    }

    public function toArray(string $operation = 'create'): array
    {
        return array_merge([
            'type'       => $this->type,
            'name'       => $this->name,
            'label'      => $this->label,
            'hidden'     => is_callable($this->hidden) ? app()->call($this->hidden, ['operation' => $operation]) : $this->hidden,
            'conditions' => $this->conditions,
        ], $this->schema($operation));
    }

    // Override di child class untuk tambah field spesifik
    protected function schema(string $operation = 'create'): array
    {
        return [];
    }
}