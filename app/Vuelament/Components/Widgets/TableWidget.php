<?php

namespace App\Vuelament\Components\Widgets;

/**
 * TableWidget — table widget untuk dashboard
 *
 * Contoh:
 *   TableWidget::make()
 *     ->heading('Transaksi Terakhir')
 *     ->columns([
 *         ['key' => 'id',     'label' => 'ID'],
 *         ['key' => 'name',   'label' => 'Nama'],
 *         ['key' => 'amount', 'label' => 'Jumlah'],
 *     ])
 *     ->rows([
 *         ['id' => 1, 'name' => 'Order #001', 'amount' => 'Rp 500.000'],
 *         ['id' => 2, 'name' => 'Order #002', 'amount' => 'Rp 150.000'],
 *     ])
 *     ->columnSpan(2)
 */
class TableWidget extends BaseWidget
{
    protected string $type = 'Table';
    protected array $columns = [];
    protected array $rows = [];
    protected ?string $emptyMessage = null;

    public function columns(array $v): static { $this->columns = $v; return $this; }
    public function rows(array $v): static { $this->rows = $v; return $this; }
    public function emptyMessage(string $v): static { $this->emptyMessage = $v; return $this; }

    public function toArray(string $operation = 'create'): array
    {
        return [
            'type'         => $this->type,
            'heading'      => $this->heading,
            'description'  => $this->description,
            'columnSpan'   => $this->columnSpan,
            'columns'      => $this->columns,
            'rows'         => $this->rows,
            'emptyMessage' => $this->emptyMessage ?? 'Tidak ada data.',
        ];
    }
}
