<?php

declare(strict_types=1);

namespace Zadora\Lms\Modules\Ai;

use Zadora\Lms\Core\ModuleInterface;
use Zadora\Lms\Database\TableNames;
use Zadora\Lms\Http\RestRegistrar;
use Zadora\Lms\Modules\Settings\SettingsService;
use Zadora\Lms\Support\Clock;
use Zadora\Lms\Support\Container;

final class AiModule implements ModuleInterface
{
    public function register(Container $container): void
    {
        $container->singleton(AiRepository::class, static fn (): AiRepository => new AiRepository(TableNames::fromWordPress()));
        $container->singleton(AiService::class, static fn (Container $container): AiService => new AiService(
            $container->get(AiRepository::class),
            $container->get(SettingsService::class),
            new Clock()
        ));
    }

    public function boot(Container $container): void
    {
        $container->get(RestRegistrar::class)->add(new AiController($container->get(AiService::class)));
    }
}
