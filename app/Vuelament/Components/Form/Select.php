<?php

namespace App\Vuelament\Components\Form;

class Select extends BaseForm
{
    protected string $type = 'Select';
    protected array $options = [];
    protected bool $required = false;
    protected bool $disabled = false;
    protected bool $multiple = false;
    protected bool $searchable = false;
    protected string $placeholder = 'Select Option';
    protected ?string $hint = null;
    protected ?string $prefix = null;
    protected ?string $suffix = null;
    protected ?string $prefixIcon = null;
    protected ?string $suffixIcon = null;
    protected ?string $optionsFrom = null;
    protected ?array $createOptionSchema = null;
    protected ?string $createOptionEndpoint = null;
    protected ?string $createOptionLabel = null;

    /**
     * Options bisa berbagai format:
     *   ['admin', 'user']
     *   ['admin' => 'Administrator']
     *   [['value' => 'x', 'label' => 'X']]
     */
    public function options(array $options): static
    {
        $this->options = $this->normalizeOptions($options);
        return $this;
    }

    /**
     * Load options dari API endpoint
     * Endpoint harus return: [{ value, label }]
     */
    public function optionsFrom(string $v): static { $this->optionsFrom = $v; return $this; }

    /**
     * Tampilkan form inline untuk buat option baru
     *
     * Contoh:
     *   ->createOptionForm(
     *       schema: [
     *           V::textInput('name')->label('Nama Kategori')->required(),
     *       ],
     *       endpoint: '/vuelament/categories',
     *       label: 'Buat Kategori Baru'
     *   )
     */
    public function createOptionForm(array $schema, string $endpoint, string $label = 'Buat Baru'): static
    {
        $this->createOptionSchema   = array_map(fn($c) => $c->toArray(), $schema);
        $this->createOptionEndpoint = $endpoint;
        $this->createOptionLabel    = $label;
        return $this;
    }

    public function required(bool $v = true): static { $this->required = $v; return $this; }
    public function disabled(bool $v = true): static { $this->disabled = $v; return $this; }
    public function multiple(bool $v = true): static { $this->multiple = $v; return $this; }
    public function searchable(bool $v = true): static { $this->searchable = $v; return $this; }
    public function placeholder(string $v): static { $this->placeholder = $v; return $this; }
    public function hint(string $v): static { $this->hint = $v; return $this; }
    public function prefix(string $v): static { $this->prefix = $v; return $this; }
    public function suffix(string $v): static { $this->suffix = $v; return $this; }
    public function prefixIcon(string $v): static { $this->prefixIcon = $v; return $this; }
    public function suffixIcon(string $v): static { $this->suffixIcon = $v; return $this; }

    protected function schema(): array
    {
        return [
            'options'              => $this->options,
            'optionsFrom'          => $this->optionsFrom,
            'required'             => $this->required,
            'disabled'             => $this->disabled,
            'multiple'             => $this->multiple,
            'searchable'           => $this->searchable,
            'placeholder'          => $this->placeholder,
            'hint'                 => $this->hint,
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