<?php

declare(strict_types=1);

namespace Zadora\Lms\Modules\Assessments;

use Zadora\Lms\Core\ModuleInterface;
use Zadora\Lms\Database\TableNames;
use Zadora\Lms\Http\RestRegistrar;
use Zadora\Lms\Support\Clock;
use Zadora\Lms\Support\Container;

final class AssessmentModule implements ModuleInterface
{
    public function register(Container $container): void
    {
        $container->singleton(AssessmentRepository::class, static fn (): AssessmentRepository => new AssessmentRepository(TableNames::fromWordPress()));
        $container->singleton(AssessmentService::class, static fn (Container $container): AssessmentService => new AssessmentService(
            $container->get(AssessmentRepository::class),
            new Clock()
        ));
    }

    public function boot(Container $container): void
    {
        $service = $container->get(AssessmentService::class);

        $container->get(RestRegistrar::class)->add(new AssessmentController($service));
    }
}
