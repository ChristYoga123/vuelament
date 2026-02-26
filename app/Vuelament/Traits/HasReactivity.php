<?php

namespace App\Vuelament\Traits;

/**
 * HasReactivity — client-side reactivity rules.
 *
 * Semua rule ini diserialize menjadi JSON dan dievaluasi di Vue (client-side).
 * TIDAK ada request server untuk show/hide/disable/required.
 *
 * Supported operators:
 *   '==='  — strict equals
 *   '!=='  — strict not equals
 *   'in'   — value in array
 *   'notIn'— value not in array
 *   'filled'— value is not empty (null, '', undefined)
 *   'blank' — value is empty
 *   '>'    — greater than (numeric)
 *   '<'    — less than (numeric)
 *   '>='   — greater than or equal (numeric)
 *   '<='   — less than or equal (numeric)
 *
 * Usage:
 *   TextInput::make('activation_date')
 *       ->visibleWhen('is_active', true)
 *       ->requiredWhen('is_active', true)
 *
 *   TextInput::make('premium_code')
 *       ->visibleWhen('type', 'premium')
 *       ->disabledWhen('is_locked', true)
 *
 *   Select::make('subcategory')
 *       ->visibleWhen('category', operator: 'filled')
 */
trait HasReactivity
{
    protected array $reactivity = [];

    // ── Visibility ──────────────────────────────────────

    public function visibleWhen(string $field, mixed $value = null, string $operator = '==='): static
    {
        $this->reactivity['visible'] = $this->buildRule($field, $value, $operator);
        return $this;
    }

    public function hiddenWhen(string $field, mixed $value = null, string $operator = '==='): static
    {
        $this->reactivity['hidden'] = $this->buildRule($field, $value, $operator);
        return $this;
    }

    // ── Disabled ────────────────────────────────────────

    public function disabledWhen(string $field, mixed $value = null, string $operator = '==='): static
    {
        $this->reactivity['disabled'] = $this->buildRule($field, $value, $operator);
        return $this;
    }

    public function enabledWhen(string $field, mixed $value = null, string $operator = '==='): static
    {
        $this->reactivity['enabled'] = $this->buildRule($field, $value, $operator);
        return $this;
    }

    // ── Required ────────────────────────────────────────

    public function requiredWhen(string $field, mixed $value = null, string $operator = '==='): static
    {
        $this->reactivity['required'] = $this->buildRule($field, $value, $operator);
        return $this;
    }

    // ── Multiple conditions (AND logic) ─────────────────

    public function visibleWhenAll(array $rules): static
    {
        $this->reactivity['visible'] = [
            'logic' => 'and',
            'rules' => array_map(fn($r) => $this->buildRule($r['field'], $r['value'] ?? null, $r['operator'] ?? '==='), $rules),
        ];
        return $this;
    }

    public function visibleWhenAny(array $rules): static
    {
        $this->reactivity['visible'] = [
            'logic' => 'or',
            'rules' => array_map(fn($r) => $this->buildRule($r['field'], $r['value'] ?? null, $r['operator'] ?? '==='), $rules),
        ];
        return $this;
    }

    // ── Getters ─────────────────────────────────────────

    public function getReactivity(): array
    {
        return $this->reactivity;
    }

    // ── Internal ────────────────────────────────────────

    protected function buildRule(string $field, mixed $value, string $operator): array
    {
        // Shortcut operators tanpa value
        if (in_array($operator, ['filled', 'blank'])) {
            return [
                'field'    => $field,
                'operator' => $operator,
            ];
        }

        return [
            'field'    => $field,
            'operator' => $operator,
            'value'    => $value,
        ];
    }
}
