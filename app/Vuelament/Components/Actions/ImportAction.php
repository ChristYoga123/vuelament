<?php

namespace App\Vuelament\Components\Actions;

class ImportAction extends BaseAction
{
    protected string $type = 'ImportAction';
    protected string $label = 'Import';
    protected ?string $icon = 'upload';
    protected ?string $color = 'info';
    protected array $acceptedFormats = ['csv', 'xlsx'];
    protected ?string $importEndpoint = null;
    protected ?string $templateUrl = null;

    public function acceptedFormats(array $v): static { $this->acceptedFormats = $v; return $this; }
    public function importEndpoint(string $v): static { $this->importEndpoint = $v; return $this; }
    public function templateUrl(string $v): static { $this->templateUrl = $v; return $this; }

    protected function schema(): array
    {
        return [
            'acceptedFormats' => $this->acceptedFormats,
            'importEndpoint'  => $this->importEndpoint,
            'templateUrl'     => $this->templateUrl,
        ];
    }
}
