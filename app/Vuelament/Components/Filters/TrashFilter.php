<?php

namespace App\Vuelament\Components\Filters;

class TrashFilter extends SelectFilter
{
    protected string $type = 'TrashFilter';

    public function __construct(string $name = 'trashed')
    {
        parent::__construct($name);
        $this->isTrashed = true;
        
        $this->label('Trash Status')
             ->placeholder('Without Trashed')
             ->options([
                 'with' => 'With Trashed',
                 'only' => 'Only Trashed',
             ]);
    }
}
