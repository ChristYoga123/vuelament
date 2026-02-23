<?php

namespace App\Vuelament\Components\Actions;

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
    protected ?\Closure $actionCallback = null;

    /**
     * Form modal sebelum action dijalankan
     */
    public function form(array $schema): static
    {
        $this->formSchema = array_map(fn($c) => $c->toArray(), $schema);
        return $this;
    }

    /**
     * Server-side callback
     */
    public function action(\Closure $callback): static
    {
        $this->actionCallback = $callback;
        return $this;
    }

    public function execute(array $data = []): mixed
    {
        if ($this->actionCallback) {
            return ($this->actionCallback)($data);
        }
        return null;
    }

    protected function schema(): array
    {
        return [
            'formSchema' => $this->formSchema,
        ];
    }
}
