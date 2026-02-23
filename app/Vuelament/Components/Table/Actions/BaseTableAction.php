<?php

namespace App\Vuelament\Components\Table\Actions;

/**
 * BaseTableAction — base class untuk row-level table actions
 * Dipisahkan dari BaseAction (page-level) karena konteksnya berbeda:
 * row action menerima record/id dari baris tabel
 */
abstract class BaseTableAction
{
    protected string $type = '';
    protected string $name = '';
    protected string $label = '';
    protected ?string $icon = null;
    protected ?string $color = null;
    protected ?string $url = null;
    protected ?string $endpoint = null;
    protected ?string $method = 'POST';
    protected bool $requiresConfirmation = false;
    protected ?string $confirmationTitle = null;
    protected ?string $confirmationMessage = null;
    protected bool $hidden = false;
    protected bool $disabled = false;
    protected ?string $tooltip = null;

    public function __construct(string $name = '')
    {
        $this->name = $name;
    }

    public static function make(string $name = ''): static
    {
        return new static($name);
    }

    public function label(string $v): static { $this->label = $v; return $this; }
    public function icon(string $v): static { $this->icon = $v; return $this; }
    public function color(string $v): static { $this->color = $v; return $this; }
    public function url(string $v): static { $this->url = $v; return $this; }
    public function endpoint(string $v, string $method = 'POST'): static { $this->endpoint = $v; $this->method = $method; return $this; }
    public function hidden(bool $v = true): static { $this->hidden = $v; return $this; }
    public function disabled(bool $v = true): static { $this->disabled = $v; return $this; }
    public function tooltip(string $v): static { $this->tooltip = $v; return $this; }

    public function requiresConfirmation(
        string $title = 'Konfirmasi',
        string $message = 'Apakah Anda yakin?'
    ): static {
        $this->requiresConfirmation = true;
        $this->confirmationTitle = $title;
        $this->confirmationMessage = $message;
        return $this;
    }

    public function toArray(): array
    {
        return array_merge([
            'type'                 => $this->type,
            'name'                 => $this->name,
            'label'                => $this->label,
            'icon'                 => $this->icon,
            'color'                => $this->color,
            'url'                  => $this->url,
            'endpoint'             => $this->endpoint,
            'method'               => $this->method,
            'hidden'               => $this->hidden,
            'disabled'             => $this->disabled,
            'tooltip'              => $this->tooltip,
            'requiresConfirmation' => $this->requiresConfirmation,
            'confirmationTitle'    => $this->confirmationTitle,
            'confirmationMessage'  => $this->confirmationMessage,
        ], $this->schema());
    }

    protected function schema(): array
    {
        return [];
    }
}
