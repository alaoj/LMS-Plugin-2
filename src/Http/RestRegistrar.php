<?php

declare(strict_types=1);

namespace Zadora\Lms\Http;

final class RestRegistrar
{
    public const NAMESPACE = 'zadora/v1';

    /** @var ControllerInterface[] */
    private array $controllers = [];

    public function add(ControllerInterface $controller): void
    {
        $this->controllers[] = $controller;
    }

    public function register(): void
    {
        foreach ($this->controllers as $controller) {
            $controller->registerRoutes();
        }
    }
}
