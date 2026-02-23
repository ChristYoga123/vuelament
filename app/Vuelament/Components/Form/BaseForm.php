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
    protected bool $isLive = false;
    protected mixed $default = null;
    protected array $customRules = [];

    // Unique validation
    protected bool $isUnique = false;
    protected ?string $uniqueTable = null;
    protected ?string $uniqueColumn = null;
    protected bool $uniqueIgnoreRecord = false;

    public function dehydrated(bool|\Closure $v = true): static { $this->isDehydrated = $v; return $this; }
    public function live(bool $v = true): static { $this->isLive = $v; return $this; }
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
     *
     * Contoh:
     *   ->rules(['regex:/^[a-z]+$/'])
     */
    public function rules(array $rules): static { $this->customRules = $rules; return $this; }

    /**
     * Unique validation — mirip Filament
     *
     * Contoh:
     *   ->unique('users', 'email')                 // unique:users,email
     *   ->unique('users', 'email', ignoreRecord: true)  // unique:users,email,{id} — skip record yang sedang di-edit
     *   ->unique()                                  // otomatis pakai nama field sebagai column
     *
     * Saat ignoreRecord: true, ResourceController akan inject ID record ke rule:
     *   'unique:users,email,{id}'
     */
    public function unique(?string $table = null, ?string $column = null, bool $ignoreRecord = false): static
    {
        $this->isUnique = true;
        // if user explicitly passed table, use it
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

    public function getRequiredProp(): bool|\Closure { return false; }

    /**
     * Build validation rules otomatis dari properti komponen
     *
     * @param mixed $recordId ID record saat edit (untuk unique ignore)
     */
    public function getValidationRules(mixed $recordId = null, string $operation = 'create', ?string $tableFallback = null): array
    {
        $rules = [];
        $schema = $this->schema();

        // Evaluate required locally rather than strictly relying on array if the component property exists
        $isRequired = !empty($schema['required']);
        if (method_exists($this, 'getRequiredProp')) {
            $reqProp = $this->getRequiredProp();
            if (is_callable($reqProp)) {
                $isRequired = app()->call($reqProp, ['operation' => $operation]);
            } else {
                $isRequired = $reqProp;
            }
        }

        // Required
        if ($isRequired) {
            $rules[] = 'required';
        } else {
            $rules[] = 'nullable';
        }

        // String-based fields (default)
        if (isset($schema['inputType'])) {
            match ($schema['inputType']) {
                'email'    => $rules[] = 'email',
                'number'   => $rules[] = 'numeric',
                'url'      => $rules[] = 'url',
                default    => $rules[] = 'string',
            };
        }

        // Min length
        if (!empty($schema['minLength'])) {
            $rules[] = 'min:' . $schema['minLength'];
        }

        // Max length
        if (!empty($schema['maxLength'])) {
            $rules[] = 'max:' . $schema['maxLength'];
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

    /**
     * Apakah unique validation perlu ignore record saat edit
     */
    public function shouldIgnoreRecord(): bool { return $this->uniqueIgnoreRecord; }

    /**
     * Get nama field
     */
    public function getName(): string
    {
        return $this->name;
    }

    public function toArray(string $operation = 'create'): array
    {
        return array_merge(parent::toArray($operation), [
            'default'              => $this->default,
            'isDehydrated'         => is_callable($this->isDehydrated) ? true : $this->isDehydrated,
            'isLive'               => $this->isLive,
            'hasAfterStateUpdated' => $this->afterStateUpdated !== null,
        ]);
    }
}
