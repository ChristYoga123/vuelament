<?php

namespace ChristYoga123\Vuelament\Components\Actions;

/**
 * Action — custom action (page/header level)
 *
 * Contoh:
 *   V::action('approve')
 *     ->label('Approve All')
 *     ->icon('check')
 *     ->color('success')
 *     ->endpoint('/api/approve-all')
 *     ->requiresConfirmation()
 */
class Action extends BaseAction
{
    protected string $type = 'Action';
    protected array $formSchema = [];
    protected \Closure|array|null $actionCallback = null;

    /**
     * Form modal sebelum action dijalankan
     */
    public function form(array $schema): static
    {
        $this->formSchema = array_map(fn($c) => $c->toArray(), $schema);
        return $this;
    }

    /**
     * Server-side callback — supports both Closure and Service array callable
     *
     * Closure:
     *   ->action(fn(array $data) => ...)
     *
     * Service callable (uses app()->call() for DI):
     *   ->action([ProductService::class, 'publish'])
     */
    public function action(\Closure|array $callback): static
    {
        $this->actionCallback = $callback;
        return $this;
    }

    /**
     * Execute the action callback
     *
     * Array callable → app()->call() (Laravel DI)
     * Closure → direct invocation
     */
    public function execute(array $data = []): mixed
    {
        if (!$this->actionCallback) {
            return null;
        }

        // Service-based: [ServiceClass::class, 'method']
        if (is_array($this->actionCallback)) {
            [$class, $method] = $this->actionCallback;
            $instance = app($class);

            return app()->call([$instance, $method], [
                'data' => $data,
            ]);
        }

        // Closure
        return ($this->actionCallback)($data);
    }

    protected function schema(): array
    {
        return [
            'formSchema' => $this->formSchema,
        ];
    }
}
