<?php

namespace App\Vuelament\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class PageController
{
    /**
     * Handle custom page rendering
     * Route-nya auto-registered dari panel->pages
     */
    public function __invoke(Request $request, string $pageClass)
    {
        $panel = app('vuelament.panel');

        $data = array_merge(
            $pageClass::getData(),
            [
                'panel' => $panel->toArray(),
                'auth'  => ['user' => $request->user()],
                'page'  => [
                    'title' => $pageClass::getTitle(),
                    'slug'  => $pageClass::getSlug(),
                    'icon'  => $pageClass::getIcon(),
                ],
            ]
        );

        return Inertia::render($pageClass::getView(), $data);
    }
}
