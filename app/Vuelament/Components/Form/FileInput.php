<?php

namespace App\Vuelament\Components\Form;

class FileInput extends BaseForm
{
    protected string $type = 'FileInput';
    protected bool $required = false;
    protected bool $disabled = false;
    protected ?string $hint = null;
    protected bool $multiple = false;
    protected array $acceptedFileTypes = [];
    protected ?int $maxSize = null;        // in KB
    protected ?int $maxFiles = null;
    protected ?string $directory = null;   // upload directory
    protected bool $image = false;
    protected bool $avatar = false;
    protected ?string $placeholder = null;

    public function required(bool $v = true): static { $this->required = $v; return $this; }
    public function disabled(bool $v = true): static { $this->disabled = $v; return $this; }
    public function hint(string $v): static { $this->hint = $v; return $this; }
    public function multiple(bool $v = true): static { $this->multiple = $v; return $this; }
    public function acceptedFileTypes(array $v): static { $this->acceptedFileTypes = $v; return $this; }
    public function maxSize(int $v): static { $this->maxSize = $v; return $this; }
    public function maxFiles(int $v): static { $this->maxFiles = $v; return $this; }
    public function directory(string $v): static { $this->directory = $v; return $this; }
    public function placeholder(string $v): static { $this->placeholder = $v; return $this; }

    public function image(): static
    {
        $this->image = true;
        $this->acceptedFileTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
        return $this;
    }

    public function avatar(): static
    {
        $this->avatar = true;
        return $this->image();
    }

    protected function schema(): array
    {
        return [
            'required'          => $this->required,
            'disabled'          => $this->disabled,
            'hint'              => $this->hint,
            'multiple'          => $this->multiple,
            'acceptedFileTypes' => $this->acceptedFileTypes,
            'maxSize'           => $this->maxSize,
            'maxFiles'          => $this->maxFiles,
            'directory'         => $this->directory,
            'image'             => $this->image,
            'avatar'            => $this->avatar,
            'placeholder'       => $this->placeholder,
        ];
    }
}
