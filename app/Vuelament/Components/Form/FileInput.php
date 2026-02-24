<?php

namespace App\Vuelament\Components\Form;

class FileInput extends BaseForm
{
    protected string $type = 'FileInput';
    protected bool|\Closure $multiple = false;
    protected array $acceptedFileTypes = [];
    protected ?int $maxSize = null;        // in KB
    protected ?int $maxFiles = null;
    protected ?string $directory = null;   // upload directory
    protected bool|\Closure $image = false;
    protected bool|\Closure $avatar = false;
    protected bool|\Closure $reorderable = false;

    public function multiple(bool|\Closure $v = true): static { $this->multiple = $v; return $this; }
    public function acceptedFileTypes(array $v): static { $this->acceptedFileTypes = $v; return $this; }
    public function maxSize(int $v): static { $this->maxSize = $v; return $this; }
    public function maxFiles(int $v): static { $this->maxFiles = $v; return $this; }
    public function directory(string $v): static { $this->directory = $v; return $this; }
    public function reorderable(bool|\Closure $v = true): static { $this->reorderable = $v; return $this; }

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

    protected function schema(string $operation = 'create'): array
    {
        return [
            'multiple'          => $this->evaluate($this->multiple, $operation),
            'acceptedFileTypes' => $this->acceptedFileTypes,
            'maxSize'           => $this->maxSize,
            'maxFiles'          => $this->maxFiles,
            'directory'         => $this->directory,
            'image'             => $this->evaluate($this->image, $operation),
            'avatar'            => $this->evaluate($this->avatar, $operation),
            'reorderable'       => $this->evaluate($this->reorderable, $operation),
        ];
    }
}
