<?php

declare(strict_types=1);

namespace Zadora\Lms\Modules\Companies;

use Zadora\Lms\Core\ModuleInterface;
use Zadora\Lms\Database\TableNames;
use Zadora\Lms\Http\RestRegistrar;
use Zadora\Lms\Support\Clock;
use Zadora\Lms\Support\Container;

final class CompanyModule implements ModuleInterface
{
    public function register(Container $container): void
    {
        $container->singleton(CompanyRepository::class, static fn (): CompanyRepository => new CompanyRepository(TableNames::fromWordPress()));
        $container->singleton(CompanyService::class, static fn (Container $container): CompanyService => new CompanyService(
            $container->get(CompanyRepository::class),
            new Clock()
        ));
    }

    public function boot(Container $container): void
    {
        $container->get(RestRegistrar::class)->add(new CompanyController($container->get(CompanyService::class)));
    }
}
