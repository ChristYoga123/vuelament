<?php

namespace App\Vuelament\Components\Actions;

/**
 * BulkAction — custom bulk action pada selected rows
 *
 * Contoh:
 *   V::bulkAction('approve')
 *     ->label('Approve Selected')
 *     ->icon('check')
 *     ->color('success')
 *     ->endpoint('/api/bulk-approve')
 *     ->requiresConfirmation()
 */
class BulkAction extends BaseAction
{
    protected string $type = 'BulkAction';
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

    public function execute(array $ids, array $data = []): mixed
    {
        if ($this->actionCallback) {
            return ($this->actionCallback)($ids, $data);
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
