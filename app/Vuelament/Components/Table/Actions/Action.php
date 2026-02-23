<?php

namespace App\Vuelament\Components\Table\Actions;

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
    protected ?\Closure $actionCallback = null;

    public function form(array $schema): static
    {
        $this->formSchema = array_map(fn($c) => $c->toArray(), $schema);
        return $this;
    }

    public function action(\Closure $callback): static
    {
        $this->actionCallback = $callback;
        return $this;
    }

    public function execute(mixed $record, array $data = []): mixed
    {
        if ($this->actionCallback) {
            return ($this->actionCallback)($record, $data);
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
