<?php

declare(strict_types=1);

namespace Zadora\Lms\Modules\Reporting;

use Zadora\Lms\Core\ModuleInterface;
use Zadora\Lms\Database\TableNames;
use Zadora\Lms\Http\RestRegistrar;
use Zadora\Lms\Support\Container;

final class ReportingModule implements ModuleInterface
{
    public function register(Container $container): void
    {
        $container->singleton(ReportingRepository::class, static fn (): ReportingRepository => new ReportingRepository(TableNames::fromWordPress()));
    }

    public function boot(Container $container): void
    {
        $container->get(RestRegistrar::class)->add(new ReportingController($container->get(ReportingRepository::class)));
    }
}
