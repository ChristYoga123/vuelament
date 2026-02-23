<?php

namespace App\Vuelament\Components\Actions;

class ExportAction extends BaseAction
{
    protected string $type = 'ExportAction';
    protected string $label = 'Export';
    protected ?string $icon = 'download';
    protected ?string $color = 'success';
    protected string $exportFormat = 'xlsx';
    protected ?string $exportEndpoint = null;
    protected ?string $fileName = null;

    public function exportFormat(string $v): static { $this->exportFormat = $v; return $this; }
    public function exportEndpoint(string $v): static { $this->exportEndpoint = $v; return $this; }
    public function fileName(string $v): static { $this->fileName = $v; return $this; }

    protected function schema(): array
    {
        return [
            'exportFormat'   => $this->exportFormat,
            'exportEndpoint' => $this->exportEndpoint,
            'fileName'       => $this->fileName,
        ];
    }
}
