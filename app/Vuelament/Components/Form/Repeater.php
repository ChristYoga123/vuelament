<?php

namespace App\Vuelament\Components\Form;

/**
 * Repeater — form component yang isinya bisa diulang (output: array)
 *
 * Contoh:
 *   V::repeater('items')
 *     ->label('Item Pembelian')
 *     ->childComponents([
 *         V::textInput('product_name')->label('Nama Produk')->required(),
 *         V::textInput('qty')->label('Qty')->number()->required(),
 *         V::textInput('price')->label('Harga')->number()->required(),
 *     ])
 *     ->minItems(1)
 *     ->maxItems(10)
 *     ->columns(3)
 *     ->addActionLabel('Tambah Item')
 *     ->collapsible()
 *
 * Output JSON: [
 *   { product_name: 'abc', qty: 2, price: 5000 },
 *   { product_name: 'xyz', qty: 1, price: 3000 },
 * ]
 */
class Repeater extends BaseForm
{
    protected string $type = 'Repeater';
    protected array $childSchema = [];
    protected bool $required = false;
    protected bool $disabled = false;
    protected ?int $minItems = null;
    protected ?int $maxItems = null;
    protected ?int $columns = null;
    protected string $addActionLabel = 'Tambah';
    protected bool $reorderable = true;
    protected bool $deletable = true;
    protected bool $collapsible = false;
    protected bool $collapsed = false;
    protected ?string $hint = null;

    /**
     * Set child form components (isi setiap row repeater)
     *
     * Contoh:
     *   ->childComponents([
     *       V::textInput('name')->required(),
     *       V::textInput('qty')->number(),
     *   ])
     */
    public function childComponents(array $components): static
    {
        $this->childSchema = $components;
        return $this;
    }

    public function getComponents(): array { return $this->childSchema; }

    public function required(bool $v = true): static { $this->required = $v; return $this; }
    public function disabled(bool $v = true): static { $this->disabled = $v; return $this; }
    public function minItems(int $v): static { $this->minItems = $v; return $this; }
    public function maxItems(int $v): static { $this->maxItems = $v; return $this; }
    public function columns(int $v): static { $this->columns = $v; return $this; }
    public function addActionLabel(string $v): static { $this->addActionLabel = $v; return $this; }
    public function reorderable(bool $v = true): static { $this->reorderable = $v; return $this; }
    public function deletable(bool $v = true): static { $this->deletable = $v; return $this; }
    public function collapsible(bool $v = true): static { $this->collapsible = $v; return $this; }
    public function collapsed(bool $v = true): static { $this->collapsible = true; $this->collapsed = $v; return $this; }
    public function hint(string $v): static { $this->hint = $v; return $this; }

    /**
     * Repeater validation: array + min/max items
     */
    public function getValidationRules(mixed $recordId = null): array
    {
        $rules = [];

        $rules[] = 'array';

        if ($this->required) {
            $rules[] = 'required';
        } else {
            $rules[] = 'nullable';
        }

        if ($this->minItems !== null) {
            $rules[] = 'min:' . $this->minItems;
        }

        if ($this->maxItems !== null) {
            $rules[] = 'max:' . $this->maxItems;
        }

        $rules = array_merge($rules, $this->customRules);

        return $rules;
    }

    /**
     * Get nested validation rules untuk child items
     * Format: 'items.*.product_name' => ['required', 'string']
     */
    public function getNestedValidationRules(mixed $recordId = null, string $operation = 'create', ?string $tableFallback = null): array
    {
        $rules = [];
        foreach ($this->childSchema as $child) {
            if ($child instanceof BaseForm && $child->getName()) {
                $childRules = $child->getValidationRules($recordId, $operation, $tableFallback);
                if (!empty($childRules)) {
                    $rules[$this->name . '.*.' . $child->getName()] = $childRules;
                }
            }
        }
        return $rules;
    }

    protected function schema(string $operation = 'create'): array
    {
        return [
            'required'       => $this->required,
            'disabled'       => $this->disabled,
            'minItems'       => $this->minItems,
            'maxItems'       => $this->maxItems,
            'columns'        => $this->columns,
            'addActionLabel' => $this->addActionLabel,
            'reorderable'    => $this->reorderable,
            'deletable'      => $this->deletable,
            'collapsible'    => $this->collapsible,
            'collapsed'      => $this->collapsed,
            'hint'           => $this->hint,
            'components'     => array_map(fn($c) => $c->toArray($operation), $this->childSchema),
        ];
    }
}
