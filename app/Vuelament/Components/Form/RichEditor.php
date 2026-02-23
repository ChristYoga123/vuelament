<?php

namespace App\Vuelament\Components\Form;

class RichEditor extends BaseForm
{
    protected string $type = 'RichEditor';
    protected bool $required = false;
    protected bool $disabled = false;
    protected ?string $hint = null;
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

    public function required(bool $v = true): static { $this->required = $v; return $this; }
    public function disabled(bool $v = true): static { $this->disabled = $v; return $this; }
    public function hint(string $v): static { $this->hint = $v; return $this; }
    public function minHeight(int $v): static { $this->minHeight = $v; return $this; }
    public function toolbar(array $v): static { $this->toolbar = $v; return $this; }

    // Shorthand: hanya bold, italic, link
    public function simple(): static
    {
        $this->toolbar = ['bold', 'italic', 'link'];
        return $this;
    }

    protected function schema(): array
    {
        return [
            'required'  => $this->required,
            'disabled'  => $this->disabled,
            'hint'      => $this->hint,
            'minHeight' => $this->minHeight,
            'toolbar'   => $this->toolbar,
        ];
    }
}