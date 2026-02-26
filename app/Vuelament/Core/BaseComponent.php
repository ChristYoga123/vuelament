<?php

namespace App\Vuelament\Core;

use App\Vuelament\Traits\HasReactivity;

abstract class BaseComponent
{
    use HasReactivity;

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

    /**
     * Legacy — tampilkan komponen hanya jika field lain bernilai tertentu.
     * Contoh: ->showWhen('role', 'admin')
     *
     * Secara internal ini sekarang menambahkan reactivity rule.
     */
    public function showWhen(string $field, mixed $value): static
    {
        // Backward compat: store in conditions for old code
        $this->conditions[] = [
            'field'    => $field,
            'value'    => $value,
            'operator' => '=',
        ];

        // Also register as reactivity rule for Vue client-side evaluation
        $this->visibleWhen($field, $value);

        return $this;
    }

    public function toArray(string $operation = 'create'): array
    {
        $result = array_merge([
            'type'       => $this->type,
            'name'       => $this->name,
            'label'      => $this->label,
            'hidden'     => $this->evaluate($this->hidden, $operation),
            'conditions' => $this->conditions,
        ], $this->schema($operation));

        // Inject reactivity rules if any
        $reactivity = $this->getReactivity();
        if (!empty($reactivity)) {
            $result['reactivity'] = $reactivity;
        }

        return $result;
    }

    /**
     * Mengevaluasi value yang bisa saja Closure
     */
    protected function evaluate(mixed $value, string $operation = 'create'): mixed
    {
        if (is_callable($value)) {
            return app()->call($value, [
                'operation' => $operation,
                'get' => fn($field) => request()->input($field),
                'set' => fn($field, $val) => null,
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