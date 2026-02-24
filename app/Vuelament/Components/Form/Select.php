<?php

namespace App\Vuelament\Components\Form;

class Select extends BaseForm
{
    protected string $type = 'Select';
    protected array $options = [];
    protected bool|\Closure $multiple = false;
    protected bool|\Closure $searchable = false;
    protected ?string $prefix = null;
    protected ?string $suffix = null;
    protected ?string $prefixIcon = null;
    protected ?string $suffixIcon = null;
    protected ?string $optionsFrom = null;
    protected ?array $createOptionSchema = null;
    protected ?string $createOptionEndpoint = null;
    protected ?string $createOptionLabel = null;

    public function options(array $options): static
    {
        $this->options = $this->normalizeOptions($options);
        return $this;
    }

    public function optionsFrom(string $v): static { $this->optionsFrom = $v; return $this; }

    public function createOptionForm(array $schema, string $endpoint, string $label = 'Buat Baru'): static
    {
        $this->createOptionSchema   = array_map(fn($c) => $c->toArray(), $schema);
        $this->createOptionEndpoint = $endpoint;
        $this->createOptionLabel    = $label;
        return $this;
    }

    public function multiple(bool|\Closure $v = true): static { $this->multiple = $v; return $this; }
    public function searchable(bool|\Closure $v = true): static { $this->searchable = $v; return $this; }
    public function prefix(string $v): static { $this->prefix = $v; return $this; }
    public function suffix(string $v): static { $this->suffix = $v; return $this; }
    public function prefixIcon(string $v): static { $this->prefixIcon = $v; return $this; }
    public function suffixIcon(string $v): static { $this->suffixIcon = $v; return $this; }

    protected function schema(string $operation = 'create'): array
    {
        return [
            'options'              => $this->options,
            'optionsFrom'          => $this->optionsFrom,
            'multiple'             => $this->evaluate($this->multiple, $operation),
            'searchable'           => $this->evaluate($this->searchable, $operation),
            'prefix'               => $this->prefix,
            'suffix'               => $this->suffix,
            'prefixIcon'           => $this->prefixIcon,
            'suffixIcon'           => $this->suffixIcon,
            'createOptionSchema'   => $this->createOptionSchema,
            'createOptionEndpoint' => $this->createOptionEndpoint,
            'createOptionLabel'    => $this->createOptionLabel,
        ];
    }

    private function normalizeOptions(array $options): array
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
        return $result;
    }
}