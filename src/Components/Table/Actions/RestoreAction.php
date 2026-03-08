<?php

namespace ChristYoga123\Vuelament\Components\Table\Actions;

class RestoreAction extends BaseTableAction
{
    protected string $type = 'RestoreAction';
    protected string $label = 'Restore';
    protected ?string $icon = 'archive-restore';
    protected ?string $color = 'success';
    protected bool $requiresConfirmation = true;
    protected ?string $confirmationTitle = 'Restore Data';
    protected ?string $confirmationMessage = 'Are you sure you want to restore this record?';
}
