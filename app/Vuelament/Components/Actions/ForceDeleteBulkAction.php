<?php

namespace App\Vuelament\Components\Actions;

class ForceDeleteBulkAction extends BaseAction
{
    protected string $type = 'ForceDeleteBulkAction';
    protected string $label = 'Delete Permanently';
    protected ?string $icon = 'trash';
    protected ?string $color = 'danger';
    protected bool $requiresConfirmation = true;
    protected ?string $confirmationTitle = 'Delete Permanently';
    protected ?string $confirmationMessage = 'Data will be permanently deleted and cannot be recovered. Continue?';
}
