<?php

declare(strict_types=1);

namespace Zadora\Lms\Modules\Certificates;

use Zadora\Lms\Core\ModuleInterface;
use Zadora\Lms\Database\TableNames;
use Zadora\Lms\Http\RestRegistrar;
use Zadora\Lms\Support\Clock;
use Zadora\Lms\Support\Container;

final class CertificateModule implements ModuleInterface
{
    public function register(Container $container): void
    {
        $container->singleton(CertificateRepository::class, static fn (): CertificateRepository => new CertificateRepository(TableNames::fromWordPress()));
        $container->singleton(CertificateService::class, static fn (Container $container): CertificateService => new CertificateService(
            $container->get(CertificateRepository::class),
            new Clock()
        ));
    }

    public function boot(Container $container): void
    {
        $service = $container->get(CertificateService::class);

        $container->get(RestRegistrar::class)->add(new CertificateController($service));

        add_action(
            'zadora_lms_course_completed',
            static function (int $courseId, int $userId, int $enrollmentId) use ($service): void {
                $service->issueForCompletedCourse($courseId, $userId, $enrollmentId);
            },
            10,
            3
        );
    }
}
