<?php

namespace App\Vuelament\Components\Actions;

class DeleteBulkAction extends BaseAction
{
    protected string $type = 'DeleteBulkAction';
    protected string $label = 'Hapus';
    protected ?string $icon = 'trash';
    protected ?string $color = 'danger';
    protected bool $requiresConfirmation = true;
    protected ?string $confirmationTitle = 'Hapus Data';
    protected ?string $confirmationMessage = 'Apakah Anda yakin ingin menghapus data yang dipilih?';
}
