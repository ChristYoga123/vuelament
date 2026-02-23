<?php

namespace App\Vuelament\Components\Filters;

/**
 * CustomFilter — filter dengan form component custom + query callback
 *
 * Contoh:
 *   V::customFilter('price_range')
 *     ->label('Rentang Harga')
 *     ->form([
 *         V::textInput('min')->label('Min')->number(),
 *         V::textInput('max')->label('Max')->number(),
 *     ])
 *     ->query(function ($query, array $data) {
 *         return $query
 *             ->when($data['min'], fn($q, $v) => $q->where('price', '>=', $v))
 *             ->when($data['max'], fn($q, $v) => $q->where('price', '<=', $v));
 *     })
 */
class CustomFilter extends BaseFilter
{
    protected string $type = 'CustomFilter';
    protected array $formSchema = [];
    protected ?\Closure $queryCallback = null;

    /**
     * Isi form component untuk filter ini
     * Menerima array of BaseForm components
     */
    public function form(array $schema): static
    {
        $this->formSchema = array_map(fn($c) => $c->toArray(), $schema);
        return $this;
    }

    /**
     * Custom query callback
     * fn($query, array $data) => $query
     */
    public function query(\Closure $callback): static
    {
        $this->queryCallback = $callback;
        return $this;
    }

    /**
     * Eksekusi filter ke query builder
     */
    public function apply($query, array $data): mixed
    {
        if ($this->queryCallback) {
            return ($this->queryCallback)($query, $data);
        }
        return $query;
    }

    protected function schema(): array
    {
        return [
            'formSchema' => $this->formSchema,
        ];
    }
}
