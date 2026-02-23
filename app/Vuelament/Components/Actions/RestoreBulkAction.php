<?php

namespace App\Vuelament\Components\Actions;

class RestoreBulkAction extends BaseAction
{
    protected string $type = 'RestoreBulkAction';
    protected string $label = 'Restore';
    protected ?string $icon = 'archive-restore';
    protected ?string $color = 'warning';
    protected bool $requiresConfirmation = true;
    protected ?string $confirmationTitle = 'Restore Data';
    protected ?string $confirmationMessage = 'Apakah Anda yakin ingin merestore data yang dipilih?';
}
