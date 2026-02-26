<?php

namespace App\Vuelament\Components\Table\Actions;

class ForceDeleteAction extends BaseTableAction
{
    protected string $type = 'ForceDeleteAction';
    protected string $label = 'Delete Permanently';
    protected ?string $icon = 'trash';
    protected ?string $color = 'danger';
    protected bool $requiresConfirmation = true;
    protected ?string $confirmationTitle = 'Delete Permanently';
    protected ?string $confirmationMessage = 'Data will be permanently deleted and cannot be recovered. Continue?';
}
