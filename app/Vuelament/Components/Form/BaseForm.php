<?php

namespace App\Vuelament\Components\Form;

use App\Vuelament\Core\BaseComponent;

abstract class BaseForm extends BaseComponent
{
    protected bool|\Closure $isDehydrated = true;
    protected ?\Closure $afterStateUpdated = null;
    protected ?\Closure $afterStateHydrated = null;
    protected ?\Closure $beforeStateDehydrated = null;
    protected ?\Closure $dehydrateStateUsing = null;
    protected bool|\Closure $isLive = false;
    protected mixed $default = null;
    protected array $customRules = [];

    // ── Common form attributes ──────────────────────────
    protected bool|\Closure $required = false;
    protected bool|\Closure $disabled = false;
    protected bool|\Closure $readonly = false;
    protected ?string $placeholder = null;
    protected ?string $hint = null;
    protected ?int $maxLength = null;
    protected ?int $minLength = null;

    // Unique validation
    protected bool $isUnique = false;
    protected ?string $uniqueTable = null;
    protected ?string $uniqueColumn = null;
    protected bool $uniqueIgnoreRecord = false;

    // ── Common setters ──────────────────────────────────
    public function required(bool|\Closure $v = true): static { $this->required = $v; return $this; }
    public function disabled(bool|\Closure $v = true): static { $this->disabled = $v; return $this; }
    public function readonly(bool|\Closure $v = true): static { $this->readonly = $v; return $this; }
    public function placeholder(string $v): static { $this->placeholder = $v; return $this; }
    public function hint(string $v): static { $this->hint = $v; return $this; }
    public function maxLength(int $v): static { $this->maxLength = $v; return $this; }
    public function minLength(int $v): static { $this->minLength = $v; return $this; }

    // ── State lifecycle ─────────────────────────────────
    public function dehydrated(bool|\Closure $v = true): static { $this->isDehydrated = $v; return $this; }
    public function live(bool|\Closure $v = true): static { $this->isLive = $v; return $this; }
    public function default(mixed $v): static { $this->default = $v; return $this; }
    public function afterStateUpdated(\Closure $v): static { $this->afterStateUpdated = $v; return $this; }
    public function afterStateHydrated(\Closure $v): static { $this->afterStateHydrated = $v; return $this; }
    public function beforeStateDehydrated(\Closure $v): static { $this->beforeStateDehydrated = $v; return $this; }
    public function dehydrateStateUsing(\Closure $v): static { $this->dehydrateStateUsing = $v; return $this; }
    public function saved(bool|\Closure $v = true): static { $this->isDehydrated = $v; return $this; }

    public function getIsDehydrated(): bool|\Closure { return $this->isDehydrated; }
    public function getDehydrateStateUsing(): ?\Closure { return $this->dehydrateStateUsing; }

    /**
     * Tambahkan custom validation rules
     */
    public function rules(array $rules): static { $this->customRules = $rules; return $this; }

    /**
     * Unique validation
     */
    public function unique(?string $table = null, ?string $column = null, bool $ignoreRecord = false): static
    {
        $this->isUnique = true;
        if ($table !== null) {
            $this->uniqueTable = $table;
        }
        $this->uniqueColumn = $column;
        $this->uniqueIgnoreRecord = $ignoreRecord;
        return $this;
    }

    public function uniqueIgnoreRecord(bool $v = true): static
    {
        $this->isUnique = true;
        $this->uniqueIgnoreRecord = $v;
        return $this;
    }

    public function getRequiredProp(): bool|\Closure { return $this->required; }

    /**
     * Resolve a bool|Closure value, injecting operation context.
     */
    protected function evaluate(mixed $value, string $operation = 'create'): mixed
    {
        if (is_callable($value)) {
            return app()->call($value, ['operation' => $operation]);
        }
        return $value;
    }

    /**
     * Common schema fields — merged automatically with child schema
     */
    protected function baseSchema(string $operation = 'create'): array
    {
        return [
            'required'    => $this->evaluate($this->required, $operation),
            'disabled'    => $this->evaluate($this->disabled, $operation),
            'readonly'    => $this->evaluate($this->readonly, $operation),
            'placeholder' => $this->placeholder,
            'hint'        => $this->hint,
            'maxLength'   => $this->maxLength,
            'minLength'   => $this->minLength,
        ];
    }

    /**
     * Build validation rules otomatis dari properti komponen
     */
    public function getValidationRules(mixed $recordId = null, string $operation = 'create', ?string $tableFallback = null): array
    {
        $rules = [];
        $schema = $this->schema($operation);
        $base = $this->baseSchema($operation);

        $isRequired = $base['required'];

        if ($isRequired) {
            $rules[] = 'required';
        } else {
            $rules[] = 'nullable';
        }

        // String-based fields (default)
        if (isset($schema['inputType'])) {
            match ($schema['inputType']) {
                'email'    => $rules[] = 'email:rfc,dns',
                'number'   => $rules[] = 'numeric',
                'url'      => $rules[] = 'url',
                default    => $rules[] = 'string',
            };
        }

        // Min length
        if (!empty($base['minLength'])) {
            $rules[] = 'min:' . $base['minLength'];
        }

        // Max length
        if (!empty($base['maxLength'])) {
            $rules[] = 'max:' . $base['maxLength'];
        }

        // File
        if ($this->type === 'FileInput') {
            $rules[] = 'file';
            if (!empty($schema['maxSize'])) {
                $rules[] = 'max:' . $schema['maxSize'];
            }
            if (!empty($schema['image'])) {
                $rules[] = 'image';
            }
        }

        // Unique
        if ($this->isUnique || $this->uniqueTable !== null || $this->uniqueColumn !== null) {
            $table  = $this->uniqueTable ?? $tableFallback ?? '';
            $column = $this->uniqueColumn ?? $this->name;
            $rule   = "unique:{$table},{$column}";

            if ($this->uniqueIgnoreRecord && $recordId !== null) {
                $rule .= ",{$recordId},id";
            }

            $rules[] = $rule;
        }

        // Merge custom rules
        $rules = array_merge($rules, $this->customRules);

        return $rules;
    }

    public function shouldIgnoreRecord(): bool { return $this->uniqueIgnoreRecord; }
    public function getName(): string { return $this->name; }

    public function toArray(string $operation = 'create'): array
    {
        return array_merge(parent::toArray($operation), $this->baseSchema($operation), [
            'default'              => $this->default,
            'isDehydrated'         => is_callable($this->isDehydrated) ? true : $this->isDehydrated,
            'isLive'               => $this->evaluate($this->isLive, $operation),
            'hasAfterStateUpdated' => $this->afterStateUpdated !== null,
        ]);
    }
}
