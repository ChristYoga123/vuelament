<?php

namespace App\Vuelament\Components\Table\Actions;

class RestoreAction extends BaseTableAction
{
    protected string $type = 'RestoreAction';
    protected string $label = 'Restore';
    protected ?string $icon = 'archive-restore';
    protected ?string $color = 'success';
    protected bool $requiresConfirmation = true;
    protected ?string $confirmationTitle = 'Restore Data';
    protected ?string $confirmationMessage = 'Apakah Anda yakin ingin merestore data ini?';
}
