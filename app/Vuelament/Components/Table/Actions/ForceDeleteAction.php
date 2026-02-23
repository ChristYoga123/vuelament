<?php

namespace App\Vuelament\Components\Table\Actions;

class ForceDeleteAction extends BaseTableAction
{
    protected string $type = 'ForceDeleteAction';
    protected string $label = 'Hapus Permanen';
    protected ?string $icon = 'trash';
    protected ?string $color = 'danger';
    protected bool $requiresConfirmation = true;
    protected ?string $confirmationTitle = 'Hapus Permanen';
    protected ?string $confirmationMessage = 'Data akan dihapus secara permanen dan tidak dapat dikembalikan. Lanjutkan?';
}
