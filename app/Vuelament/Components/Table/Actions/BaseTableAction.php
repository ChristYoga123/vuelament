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
    protected mixed $url = null;
    protected bool $shouldOpenInNewTab = false;
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
    protected array $infolist = [];

    public function __construct(string $name = '')
    {
        $this->name = $name;
        $this->label = $name ? ucfirst(str_replace('_', ' ', $name)) : '';
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
        $this->shouldOpenInNewTab = $shouldOpenInNewTab;
        return $this; 
    }
    public function openUrlInNewTab(bool $v = true): static { $this->shouldOpenInNewTab = $v; return $this; }
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
    public function infolist(array $v): static { $this->infolist = array_map(fn($c) => method_exists($c, 'toArray') ? $c->toArray() : $c, $v); return $this; }

    public function getName(): string { return $this->name; }

    public function evaluateUrl(mixed $record = null): ?string
    {
        if (is_callable($this->url)) {
            try {
                $params = ['record' => $record];
                if (is_object($record)) {
                    $params[get_class($record)] = $record;
                }
                return app()->call($this->url, $params);
            } catch (\Exception $e) {
                // Ignore evaluation error
            }
            return null;
        }
        return is_string($this->url) ? $this->url : null;
    }

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
            'url'                  => $this->evaluateUrl(null), // Evaluasi static/argument-less URL untuk config
            'shouldOpenInNewTab'   => $this->shouldOpenInNewTab,
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
            'modalCancelActionLabel'=> $this->modalCancelActionLabel ?? 'Cancel',
            'modalSubmitAction'    => $this->modalSubmitAction,
            'modalCancelAction'    => $this->modalCancelAction,
            'modalSubmitActionColor'=> $this->modalSubmitActionColor,
            'modalCancelActionColor'=> $this->modalCancelActionColor,
            'modalWidth'           => $this->modalWidth ?? 'sm:max-w-xl',
            'modalCloseByClickingAway' => $this->modalCloseByClickingAway ?? true,
            'infolist'             => $this->infolist,
        ], $this->schema());
    }

    protected function schema(): array
    {
        return [];
    }
}
