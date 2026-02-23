<?php

namespace App\Vuelament\Components\Widgets;

/**
 * ChartWidget — chart widget untuk dashboard
 *
 * Contoh:
 *   ChartWidget::make()
 *     ->heading('Revenue')
 *     ->chartType('line')
 *     ->data([
 *         'labels'   => ['Jan', 'Feb', 'Mar', 'Apr', 'May'],
 *         'datasets' => [
 *             [ 'label' => 'Revenue', 'data' => [100, 200, 150, 300, 250] ],
 *         ],
 *     ])
 *     ->columnSpan(2)
 */
class ChartWidget extends BaseWidget
{
    protected string $type = 'Chart';
    protected string $chartType = 'line';  // line, bar, pie, doughnut, area
    protected array $data = [];
    protected array $options = [];
    protected ?int $height = null;

    public function chartType(string $v): static { $this->chartType = $v; return $this; }
    public function height(int $v): static { $this->height = $v; return $this; }
    public function options(array $v): static { $this->options = $v; return $this; }

    public function data(array $data): static
    {
        $this->data = $data;
        return $this;
    }

    public function toArray(string $operation = 'create'): array
    {
        return [
            'type'        => $this->type,
            'heading'     => $this->heading,
            'description' => $this->description,
            'columnSpan'  => $this->columnSpan,
            'chartType'   => $this->chartType,
            'data'        => $this->data,
            'options'     => $this->options,
            'height'      => $this->height,
        ];
    }
}
