<?php

declare(strict_types=1);

namespace Zadora\Lms\Http;

interface ControllerInterface
{
    public function registerRoutes(): void;
}
