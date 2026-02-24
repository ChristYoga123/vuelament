<?php

namespace App\Vuelament\Components\Actions;

class ForceDeleteBulkAction extends BaseAction
{
    protected string $type = 'ForceDeleteBulkAction';
    protected string $label = 'Hapus Permanen';
    protected ?string $icon = 'trash';
    protected ?string $color = 'danger';
    protected bool $requiresConfirmation = true;
    protected ?string $confirmationTitle = 'Hapus Permanen';
    protected ?string $confirmationMessage = 'Data akan dihapus secara permanen dan tidak dapat dikembalikan. Lanjutkan?';
}
