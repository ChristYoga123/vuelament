<?php

namespace ChristYoga123\Vuelament\Components\Table\Actions;

/**
 * Action — custom row-level table action
 *
 * Contoh:
 *   Tables\Actions\Action::make('approve')
 *     ->label('Approve')
 *     ->icon('check')
 *     ->color('success')
 *     ->endpoint('/api/approve/{id}')
 *     ->requiresConfirmation()
 */
class Action extends BaseTableAction
{
    protected string $type = 'Action';
    protected array $formSchema = [];
    protected \Closure|array|null $actionCallback = null;

    public function form(array $schema): static
    {
        $this->formSchema = $schema;
        return $this;
    }

    /**
     * Server-side callback — supports both Closure and Service array callable
     *
     * Closure:
     *   ->action(fn($record, array $data) => ...)
     *
     * Service callable (uses app()->call() for DI):
     *   ->action([ProductService::class, 'publish'])
     *
     * Execution via app()->call():
     *   app()->call([ProductService::class, 'publish'], [
     *       'product' => $record,   // injected by lowercase model basename
     *       'data'    => $data,
     *   ]);
     */
    public function action(\Closure|array $callback): static
    {
        $this->actionCallback = $callback;
        return $this;
    }

    /**
     * Execute the action callback
     *
     * Array callable → app()->call() (Laravel auto-resolves dependencies)
     * Closure → direct invocation
     */
    public function execute(mixed $record, array $data = []): mixed
    {
        if (!$this->actionCallback) {
            return null;
        }

        // Service-based: [ServiceClass::class, 'method']
        if (is_array($this->actionCallback)) {
            $params = [
                'data' => $data,
            ];

            if ($record) {
                $params[get_class($record)] = $record;
                $params['record'] = $record;
                $params[lcfirst(class_basename($record))] = $record;
            }

            // Resolve instance from container (non-static method)
            [$class, $method] = $this->actionCallback;
            $instance = app($class);

            return app()->call([$instance, $method], $params);
        }

        // Closure
        return ($this->actionCallback)($record, $data);
    }

    protected function schema(): array
    {
        return [
            'formSchema' => array_map(fn($c) => method_exists($c, 'toArray') ? $c->toArray() : $c, $this->formSchema),
        ];
    }
    
    public function getFormComponents(): array
    {
        return $this->formSchema;
    }
}
