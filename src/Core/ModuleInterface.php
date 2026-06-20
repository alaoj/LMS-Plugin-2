<?php

declare(strict_types=1);

namespace Zadora\Lms\Core;

use Zadora\Lms\Support\Container;

interface ModuleInterface
{
    public function register(Container $container): void;

    public function boot(Container $container): void;
}
