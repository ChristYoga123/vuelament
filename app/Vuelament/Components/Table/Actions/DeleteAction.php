<?php

namespace App\Vuelament\Components\Table\Actions;

class DeleteAction extends BaseTableAction
{
    protected string $type = 'DeleteAction';
    protected string $label = 'Delete';
    protected ?string $icon = 'trash';
    protected ?string $color = 'danger';
    protected bool $requiresConfirmation = true;
    protected ?string $confirmationTitle = 'Delete Data';
    protected ?string $confirmationMessage = 'Are you sure you want to delete this record?';
}
