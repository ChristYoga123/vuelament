<?php

namespace App\Vuelament\Components\Widgets;

/**
 * Stat — individual stat card
 *
 * Contoh:
 *   Stat::make('Total Users', '1,234')
 *     ->description('Pengguna aktif')
 *     ->icon('users')
 *     ->trend('+12%', 'success')
 *     ->chart([10, 25, 15, 40, 35, 50, 45])
 *     ->color('primary')
 */
class Stat
{
    protected string $label = '';
    protected string $value = '';
    protected ?string $description = null;
    protected ?string $icon = null;
    protected ?string $trend = null;
    protected ?string $trendColor = null;  // success, danger, warning, info
    protected array $chart = [];
    protected string $color = 'primary';

    public function __construct(string $label, string $value)
    {
        $this->label = $label;
        $this->value = $value;
    }

    public static function make(string $label, string $value): static
    {
        return new static($label, $value);
    }

    public function description(string $v): static { $this->description = $v; return $this; }
    public function icon(string $v): static { $this->icon = $v; return $this; }
    public function color(string $v): static { $this->color = $v; return $this; }

    public function trend(string $value, string $color = 'success'): static
    {
        $this->trend = $value;
        $this->trendColor = $color;
        return $this;
    }

    /**
     * Mini sparkline data (array of numbers)
     */
    public function chart(array $data): static
    {
        $this->chart = $data;
        return $this;
    }

    public function toArray(string $operation = 'create'): array
    {
        return [
            'label'       => $this->label,
            'value'       => $this->value,
            'description' => $this->description,
            'icon'        => $this->icon,
            'trend'       => $this->trend,
            'trendColor'  => $this->trendColor,
            'chart'       => $this->chart,
            'color'       => $this->color,
        ];
    }
}
