<?php

namespace App\Vuelament\Components\Actions;

class DeleteBulkAction extends BaseAction
{
    protected string $type = 'DeleteBulkAction';
    protected string $label = 'Delete';
    protected ?string $icon = 'trash';
    protected ?string $color = 'danger';
    protected bool $requiresConfirmation = true;
    protected ?string $confirmationTitle = 'Delete Data';
    protected ?string $confirmationMessage = 'Are you sure you want to delete the selected records?';
}
