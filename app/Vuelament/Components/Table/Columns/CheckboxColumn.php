<?php

namespace App\Vuelament\Components\Table\Columns;

use App\Vuelament\Components\Table\Column;

class CheckboxColumn extends Column
{
    protected string $type = 'CheckboxColumn';

    public function __construct(string $name)
    {
        parent::__construct($name);
        $this->isToggle = false;
    }
}
