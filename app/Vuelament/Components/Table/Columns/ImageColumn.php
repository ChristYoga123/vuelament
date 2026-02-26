<?php

namespace App\Vuelament\Components\Table\Columns;

use App\Vuelament\Components\Table\Column;

class ImageColumn extends Column
{
    protected string $type = 'ImageColumn';
    protected bool $isThumbnail = false;
    protected bool $isCircle = false;
    protected string $sizeValue = '40px';

    public function thumbnail(bool $v = true): static
    {
        $this->isThumbnail = $v;
        return $this;
    }

    public function circle(bool $v = true): static
    {
        $this->isCircle = $v;
        return $this;
    }

    public function size(string $size): static
    {
        $this->sizeValue = $size;
        return $this;
    }

    public function toArray(string $operation = 'create'): array
    {
        return array_merge(parent::toArray($operation), [
            'isThumbnail' => $this->isThumbnail,
            'isCircle' => $this->isCircle,
            'size' => $this->sizeValue,
        ]);
    }
}
