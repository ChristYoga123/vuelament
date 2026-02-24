<?php

namespace App\Vuelament\Components\Form;

/**
 * Repeater — form component yang isinya bisa diulang (output: array)
 */
class Repeater extends BaseForm
{
    protected string $type = 'Repeater';
    protected array $childSchema = [];
    protected ?int $minItems = null;
    protected ?int $maxItems = null;
    protected ?int $columns = null;
    protected string $addActionLabel = 'Tambah';
    protected bool|\Closure $reorderable = true;
    protected bool|\Closure $deletable = true;
    protected bool|\Closure $collapsible = false;
    protected bool|\Closure $collapsed = false;

    public function childComponents(array $components): static
    {
        $this->childSchema = $components;
        return $this;
    }

    public function getComponents(): array { return $this->childSchema; }

    public function minItems(int $v): static { $this->minItems = $v; return $this; }
    public function maxItems(int $v): static { $this->maxItems = $v; return $this; }
    public function columns(int $v): static { $this->columns = $v; return $this; }
    public function addActionLabel(string $v): static { $this->addActionLabel = $v; return $this; }
    public function reorderable(bool|\Closure $v = true): static { $this->reorderable = $v; return $this; }
    public function deletable(bool|\Closure $v = true): static { $this->deletable = $v; return $this; }
    public function collapsible(bool|\Closure $v = true): static { $this->collapsible = $v; return $this; }
    public function collapsed(bool|\Closure $v = true): static { $this->collapsible = true; $this->collapsed = $v; return $this; }

    /**
     * Repeater validation: array + min/max items
     */
    public function getValidationRules(mixed $recordId = null, string $operation = 'create', ?string $tableFallback = null): array
    {
        $rules = [];

        $rules[] = 'array';

        $isRequired = $this->required;
        if (is_callable($isRequired)) {
            $isRequired = app()->call($isRequired, ['operation' => $operation]);
        }

        if ($isRequired) {
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
            'minItems'       => $this->minItems,
            'maxItems'       => $this->maxItems,
            'columns'        => $this->columns,
            'addActionLabel' => $this->addActionLabel,
            'reorderable'    => $this->evaluate($this->reorderable, $operation),
            'deletable'      => $this->evaluate($this->deletable, $operation),
            'collapsible'    => $this->evaluate($this->collapsible, $operation),
            'collapsed'      => $this->evaluate($this->collapsed, $operation),
            'components'     => array_map(fn($c) => $c->toArray($operation), $this->childSchema),
        ];
    }
}
