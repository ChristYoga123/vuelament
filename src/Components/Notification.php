<?php

namespace ChristYoga123\Vuelament\Components;

class Notification
{
    protected string $type = 'info';
    protected string $titleText = '';
    protected string $bodyText = '';

    public static function make(): static
    {
        return new static();
    }

    public function success(): static
    {
        $this->type = 'success';
        return $this;
    }

    public function info(): static
    {
        $this->type = 'info';
        return $this;
    }

    public function danger(): static
    {
        $this->type = 'danger';
        return $this;
    }

    public function warning(): static
    {
        $this->type = 'warning';
        return $this;
    }

    public function title(string $title): static
    {
        $this->titleText = $title;
        return $this;
    }

    public function body(string $body): static
    {
        $this->bodyText = $body;
        return $this;
    }

    public function send(): void
    {
        $notifications = session()->get('_vuelament_notifications', []);

        $notifications[] = [
            'type' => $this->type,
            'title' => $this->titleText,
            'body' => $this->bodyText,
        ];

        session()->flash('_vuelament_notifications', $notifications);
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'title' => $this->titleText,
            'body' => $this->bodyText,
        ];
    }
}
