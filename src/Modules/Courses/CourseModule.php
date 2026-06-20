<?php

declare(strict_types=1);

namespace Zadora\Lms\Modules\Courses;

use Zadora\Lms\Core\ModuleInterface;
use Zadora\Lms\Database\TableNames;
use Zadora\Lms\Http\RestRegistrar;
use Zadora\Lms\Support\Clock;
use Zadora\Lms\Support\Container;

final class CourseModule implements ModuleInterface
{
    public function register(Container $container): void
    {
        $container->singleton(CourseRepository::class, static fn (): CourseRepository => new CourseRepository(TableNames::fromWordPress()));
        $container->singleton(CourseService::class, static fn (Container $container): CourseService => new CourseService(
            $container->get(CourseRepository::class),
            new Clock()
        ));
    }

    public function boot(Container $container): void
    {
        $container->get(RestRegistrar::class)->add(new CourseController($container->get(CourseService::class)));
    }
}
