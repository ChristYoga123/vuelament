<?php

namespace App\Vuelament\Components\Widgets;

/**
 * StatsOverviewWidget — menampilkan grid stats card
 *
 * Contoh:
 *   StatsOverviewWidget::make()
 *     ->heading('Overview')
 *     ->stats([
 *         Stat::make('Total Users', '1,234')->icon('users')->trend('+12%', 'success'),
 *         Stat::make('Revenue', 'Rp 5.2M')->icon('dollar-sign')->trend('+8%', 'success'),
 *         Stat::make('Orders', '342')->icon('shopping-cart')->trend('-3%', 'danger'),
 *     ])
 */
class StatsOverviewWidget extends BaseWidget
{
    protected string $type = 'StatsOverview';
    protected int $columns = 3;
    protected array $stats = [];

    public function columns(int $v): static { $this->columns = $v; return $this; }

    public function stats(array $stats): static
    {
        $this->stats = $stats;
        return $this;
    }

    public function toArray(string $operation = 'create'): array
    {
        return [
            'type'        => $this->type,
            'heading'     => $this->heading,
            'description' => $this->description,
            'columnSpan'  => $this->columnSpan,
            'columns'     => $this->columns,
            'stats'       => array_map(fn($s) => $s->toArray(), $this->stats),
        ];
    }
}
