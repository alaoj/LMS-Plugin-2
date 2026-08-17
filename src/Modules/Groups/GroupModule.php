<?php

declare(strict_types=1);

namespace Zadora\Lms\Modules\Groups;

use Zadora\Lms\Core\ModuleInterface;
use Zadora\Lms\Database\TableNames;
use Zadora\Lms\Http\RestRegistrar;
use Zadora\Lms\Support\Clock;
use Zadora\Lms\Support\Container;

final class GroupModule implements ModuleInterface
{
    public function register(Container $container): void
    {
        $container->singleton(GroupRepository::class, static fn (): GroupRepository => new GroupRepository(TableNames::fromWordPress()));
        $container->singleton(GroupService::class, static fn (Container $container): GroupService => new GroupService(
            $container->get(GroupRepository::class),
            new Clock()
        ));
    }

    public function boot(Container $container): void
    {
        $container->get(RestRegistrar::class)->add(new GroupController($container->get(GroupService::class)));
    }
}
