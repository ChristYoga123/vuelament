<?php

namespace App\Vuelament\Components\Filters;

class TrashFilter extends SelectFilter
{
    protected string $type = 'TrashFilter';

    public function __construct(string $name = 'trashed')
    {
        parent::__construct($name);
        $this->isTrashed = true;
        
        $this->label('Status Terhapus')
             ->placeholder('Tidak Termasuk Dihapus')
             ->options([
                 'with' => 'Termasuk Dihapus',
                 'only' => 'Hanya yang Dihapus',
             ]);
    }
}
