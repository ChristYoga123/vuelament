<?php

namespace ChristYoga123\Vuelament\Core;

class PageRegistration
{
    public function __construct(
        public string $class,
        public string $route
    ) {
    }
}
