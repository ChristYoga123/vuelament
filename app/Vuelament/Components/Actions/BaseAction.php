<?php

namespace App\Vuelament\Components\Actions;

abstract class BaseAction
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
    
    protected ?string $modalHeading = null;
    protected ?string $modalDescription = null;
    protected ?string $modalSubmitActionLabel = null;
    protected ?string $modalCancelActionLabel = null;
    protected bool $modalSubmitAction = true;
    protected bool $modalCancelAction = true;
    protected ?string $modalSubmitActionColor = null;
    protected ?string $modalCancelActionColor = null;
    protected ?string $modalWidth = null;
    protected ?bool $modalCloseByClickingAway = null;

    public function __construct(string $name)
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
    public function url(mixed $v = null, bool $shouldOpenInNewTab = false): static { 
        $this->url = $v; 
        return $this; 
    }
    public function endpoint(string $v, string $method = 'POST'): static { $this->endpoint = $v; $this->method = $method; return $this; }
    public function hidden(bool $v = true): static { $this->hidden = $v; return $this; }
    public function disabled(bool $v = true): static { $this->disabled = $v; return $this; }
    public function tooltip(string $v): static { $this->tooltip = $v; return $this; }

    public function modalHeading(?string $v): static { $this->modalHeading = $v; return $this; }
    public function modalDescription(?string $v): static { $this->modalDescription = $v; return $this; }
    public function modalSubmitActionLabel(?string $v): static { $this->modalSubmitActionLabel = $v; return $this; }
    public function modalCancelActionLabel(?string $v): static { $this->modalCancelActionLabel = $v; return $this; }
    public function modalSubmitAction(bool $v = true): static { $this->modalSubmitAction = $v; return $this; }
    public function modalCancelAction(bool $v = true): static { $this->modalCancelAction = $v; return $this; }
    public function modalSubmitActionColor(?string $v): static { $this->modalSubmitActionColor = $v; return $this; }
    public function modalCancelActionColor(?string $v): static { $this->modalCancelActionColor = $v; return $this; }
    public function modalWidth(?string $v): static { $this->modalWidth = $v; return $this; }
    public function modalCloseByClickingAway(?bool $v = true): static { $this->modalCloseByClickingAway = $v; return $this; }

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
            'modalHeading'         => $this->modalHeading ?? $this->confirmationTitle ?? $this->label,
            'modalDescription'     => $this->modalDescription ?? $this->confirmationMessage,
            'modalSubmitActionLabel'=> $this->modalSubmitActionLabel ?? 'Lanjutkan',
            'modalCancelActionLabel'=> $this->modalCancelActionLabel ?? 'Batal',
            'modalSubmitAction'    => $this->modalSubmitAction,
            'modalCancelAction'    => $this->modalCancelAction,
            'modalSubmitActionColor'=> $this->modalSubmitActionColor,
            'modalCancelActionColor'=> $this->modalCancelActionColor,
            'modalWidth'           => $this->modalWidth ?? 'sm:max-w-xl',
            'modalCloseByClickingAway' => $this->modalCloseByClickingAway ?? true,
        ], $this->schema());
    }

    protected function schema(): array
    {
        return [];
    }
}
