<?php

namespace App\Vuelament\Components\Table\Actions;

class DeleteAction extends BaseTableAction
{
    protected string $type = 'DeleteAction';
    protected string $label = 'Hapus';
    protected ?string $icon = 'trash';
    protected ?string $color = 'danger';
    protected bool $requiresConfirmation = true;
    protected ?string $confirmationTitle = 'Hapus Data';
    protected ?string $confirmationMessage = 'Apakah Anda yakin ingin menghapus data ini?';
}
