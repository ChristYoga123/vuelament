<?php

namespace App\Vuelament\Components\Table;

/**
 * Table — konfigurasi tabel dengan pagination, search, select all, columns, filters, actions
 *
 * Contoh:
 *   V::table()
 *     ->columns([...])
 *     ->filters([...])
 *     ->actions([...])
 *     ->bulkActions([...])
 *     ->headerActions([...])
 *     ->searchable()
 *     ->paginated()
 */
class Table
{
    protected array $columns = [];
    protected array $filters = [];
    protected FiltersLayout $filtersLayout = FiltersLayout::Dropdown;
    protected array $actions = [];         // row-level actions
    protected array $bulkActions = [];     // bulk actions (header)
    protected array $headerActions = [];   // page-level actions (Create, Import, Export)
    protected bool $searchable = true;
    protected bool $paginated = true;
    protected int $perPage = 10;
    protected array $perPageOptions = [10, 25, 50, 100];
    protected bool $selectable = true;
    protected ?string $defaultSort = null;
    protected string $defaultSortDirection = 'asc';
    protected ?string $emptyStateHeading = null;
    protected ?string $emptyStateDescription = null;
    protected ?string $emptyStateIcon = null;
    protected ?\Closure $queryClosure = null;

    public static function make(): static
    {
        return new static();
    }

    public function columns(array $v): static { $this->columns = $v; return $this; }
    public function filters(array $v, FiltersLayout $layout = FiltersLayout::Dropdown): static
    {
        $this->filters = $v;
        $this->filtersLayout = $layout;
        return $this;
    }
    public function filtersLayout(FiltersLayout $v): static { $this->filtersLayout = $v; return $this; }
    public function actions(array $v): static { $this->actions = $v; return $this; }
    public function bulkActions(array $v): static { $this->bulkActions = $v; return $this; }
    public function headerActions(array $v): static { $this->headerActions = $v; return $this; }
    public function searchable(bool $v = true): static { $this->searchable = $v; return $this; }
    public function paginated(bool $v = true): static { $this->paginated = $v; return $this; }
    public function perPage(int $v): static { $this->perPage = $v; return $this; }
    public function perPageOptions(array $v): static { $this->perPageOptions = $v; return $this; }
    public function selectable(bool $v = true): static { $this->selectable = $v; return $this; }
    public function emptyStateHeading(string $v): static { $this->emptyStateHeading = $v; return $this; }
    public function emptyStateDescription(string $v): static { $this->emptyStateDescription = $v; return $this; }
    public function emptyStateIcon(string $v): static { $this->emptyStateIcon = $v; return $this; }

    public function defaultSort(string $column, string $direction = 'asc'): static
    {
        $this->defaultSort = $column;
        $this->defaultSortDirection = $direction;
        return $this;
    }

    public function query(\Closure $closure): static
    {
        $this->queryClosure = $closure;
        return $this;
    }

    public function getQueryClosure(): ?\Closure
    {
        return $this->queryClosure;
    }

    public function toArray(string $operation = 'create'): array
    {
        return [
            'type'                  => 'table',
            'columns'               => array_map(fn($c) => $c->toArray(), $this->columns),
            'filters'               => array_map(fn($f) => $f->toArray(), $this->filters),
            'filtersLayout'         => $this->filtersLayout->value,
            'actions'               => array_map(fn($a) => $a->toArray(), $this->actions),
            'bulkActions'           => array_map(fn($a) => $a->toArray(), $this->bulkActions),
            'headerActions'         => array_map(fn($a) => $a->toArray(), $this->headerActions),
            'searchable'            => $this->searchable,
            'paginated'             => $this->paginated,
            'perPage'               => $this->perPage,
            'perPageOptions'        => $this->perPageOptions,
            'selectable'            => $this->selectable,
            'defaultSort'           => $this->defaultSort,
            'defaultSortDirection'  => $this->defaultSortDirection,
            'emptyStateHeading'     => $this->emptyStateHeading,
            'emptyStateDescription' => $this->emptyStateDescription,
            'emptyStateIcon'        => $this->emptyStateIcon,
        ];
    }
}
