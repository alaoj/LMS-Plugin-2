<?php

declare(strict_types=1);

namespace Zadora\Lms\Modules\Announcements;

use Zadora\Lms\Core\ModuleInterface;
use Zadora\Lms\Database\TableNames;
use Zadora\Lms\Http\RestRegistrar;
use Zadora\Lms\Support\Clock;
use Zadora\Lms\Support\Container;

final class AnnouncementModule implements ModuleInterface
{
    public function register(Container $container): void
    {
        $container->singleton(AnnouncementRepository::class, static fn (): AnnouncementRepository => new AnnouncementRepository(TableNames::fromWordPress()));
        $container->singleton(AnnouncementService::class, static fn (Container $container): AnnouncementService => new AnnouncementService(
            $container->get(AnnouncementRepository::class),
            new Clock()
        ));
    }

    public function boot(Container $container): void
    {
        $container->get(RestRegistrar::class)->add(new AnnouncementController($container->get(AnnouncementService::class)));
    }
}
