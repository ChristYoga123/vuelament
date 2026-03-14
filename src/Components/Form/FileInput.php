<?php

namespace ChristYoga123\Vuelament\Components\Form;

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
    public function maxSize(int $v): static { $this->maxSize = $v; return $this; }
    public function maxFiles(int $v): static { $this->maxFiles = $v; return $this; }
    public function directory(string $v): static { $this->directory = $v; return $this; }
    public function reorderable(bool|\Closure $v = true): static { $this->reorderable = $v; return $this; }

    public function getDirectory(): ?string { return $this->directory; }
    public function getIsMultiple(): bool { return $this->evaluate($this->multiple); }

    public function acceptedFileTypes(array $v): static
    {
        // [FIX] Filter out SVG — can contain embedded JavaScript (stored XSS)
        $this->acceptedFileTypes = array_filter(
            $v,
            fn($mime) => $mime !== 'image/svg+xml'
        );

        return $this;
    }

    public function image(): static
    {
        $this->image = true;

        // [FIX] SVG dihapus dari daftar — SVG bisa mengandung <script> / JS event handlers
        // yang menyebabkan Stored XSS jika file disajikan inline di browser.
        // Gunakan acceptedFileTypes(['image/svg+xml']) secara eksplisit jika memang diperlukan
        // dan pastikan file disajikan dengan header Content-Disposition: attachment.
        $this->acceptedFileTypes = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
        ];

        return $this;
    }

    public function avatar(): static
    {
        $this->avatar = true;
        return $this->image();
    }

    /**
     * Izinkan SVG secara eksplisit.
     * PERINGATAN: SVG dapat mengandung JavaScript. Pastikan file disajikan
     * dengan header Content-Disposition: attachment, BUKAN inline.
     */
    public function allowSvg(): static
    {
        $this->acceptedFileTypes[] = 'image/svg+xml';
        $this->acceptedFileTypes   = array_unique($this->acceptedFileTypes);

        return $this;
    }

    protected function schema(string $operation = 'create'): array
    {
        return [
            'multiple'          => $this->evaluate($this->multiple, $operation),
            'acceptedFileTypes' => array_values($this->acceptedFileTypes),
            'maxSize'           => $this->maxSize,
            'maxFiles'          => $this->maxFiles,
            'directory'         => $this->directory,
            'image'             => $this->evaluate($this->image, $operation),
            'avatar'            => $this->evaluate($this->avatar, $operation),
            'reorderable'       => $this->evaluate($this->reorderable, $operation),
        ];
    }
}
