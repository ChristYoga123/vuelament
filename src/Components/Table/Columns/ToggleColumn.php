<?php

namespace ChristYoga123\Vuelament\Components\Table\Columns;

use ChristYoga123\Vuelament\Components\Table\Column;

class ToggleColumn extends Column
{
    protected string $type = 'ToggleColumn';

    public function __construct(string $name)
    {
        parent::__construct($name);
        $this->isToggle = true; // Compatibility with VTable logic
    }
}
