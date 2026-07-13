<?php

declare(strict_types=1);

namespace Zadora\Lms\Modules\Settings;

use Zadora\Lms\Core\ModuleInterface;
use Zadora\Lms\Http\RestRegistrar;
use Zadora\Lms\Support\Container;

final class SettingsModule implements ModuleInterface
{
    public function register(Container $container): void
    {
        $container->singleton(SettingsService::class, static fn (): SettingsService => new SettingsService());
    }

    public function boot(Container $container): void
    {
        $container->get(RestRegistrar::class)->add(new SettingsController($container->get(SettingsService::class)));
    }
}
