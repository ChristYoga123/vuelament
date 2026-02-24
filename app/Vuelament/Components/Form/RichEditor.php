<?php

namespace App\Vuelament\Components\Form;

class RichEditor extends BaseForm
{
    protected string $type = 'RichEditor';
    protected ?int $minHeight = 200;
    protected array $toolbar = [
        'bold', 'italic', 'underline', 'strike',
        '|',
        'heading',
        '|',
        'bulletList', 'orderedList',
        '|',
        'link', 'blockquote', 'codeBlock',
        '|',
        'undo', 'redo',
    ];

    public function minHeight(int $v): static { $this->minHeight = $v; return $this; }
    public function toolbar(array $v): static { $this->toolbar = $v; return $this; }

    public function simple(): static
    {
        $this->toolbar = ['bold', 'italic', 'link'];
        return $this;
    }

    protected function schema(string $operation = 'create'): array
    {
        return [
            'minHeight' => $this->minHeight,
            'toolbar'   => $this->toolbar,
        ];
    }
}