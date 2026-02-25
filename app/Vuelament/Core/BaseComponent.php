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
        $this->label = $name ? ucfirst(str_replace('_', ' ', $name)) : '';
    }

    public static function make(string $name = ''): static
    {
        return new static($name);
    }

    public function label(string $label): static { $this->label = $label; return $this; }
    public function hidden(bool|\Closure $v = true): static { $this->hidden = $v; return $this; }
    public function visible(bool|\Closure $v = true): static
    {
        if (is_callable($v)) {
            $this->hidden = function (mixed ...$args) use ($v) { return !app()->call($v, $args); };
        } else {
            $this->hidden = !$v;
        }
        return $this;
    }

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
            'hidden'     => $this->evaluate($this->hidden, $operation),
            'conditions' => $this->conditions,
        ], $this->schema($operation));
    }

    /**
     * Mengevaluasi value yang bisa saja Closure
     * Injektsi $get dan $set disediakan jika form me-request state-refresh
     */
    protected function evaluate(mixed $value, string $operation = 'create'): mixed
    {
        if (is_callable($value)) {
            // Mencegah error jika callable membutuhkan $get / $set saat toArray dipanggil pertama kali (kosong)
            return app()->call($value, [
                'operation' => $operation,
                'get' => fn($field) => request()->input($field),
                'set' => fn($field, $val) => null, // Setter works only in live-validation route
            ]);
        }
        return $value;
    }

    // Override di child class untuk tambah field spesifik
    protected function schema(string $operation = 'create'): array
    {
        return [];
    }
}