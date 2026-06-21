<?php

declare(strict_types=1);

namespace Zadora\Lms\Modules\Notifications;

use Zadora\Lms\Core\ModuleInterface;
use Zadora\Lms\Database\TableNames;
use Zadora\Lms\Http\RestRegistrar;
use Zadora\Lms\Support\Clock;
use Zadora\Lms\Support\Container;

final class NotificationModule implements ModuleInterface
{
    public function register(Container $container): void
    {
        $container->singleton(NotificationRepository::class, static fn (): NotificationRepository => new NotificationRepository(TableNames::fromWordPress()));
        $container->singleton(NotificationService::class, static fn (Container $container): NotificationService => new NotificationService(
            $container->get(NotificationRepository::class),
            new Clock()
        ));
    }

    public function boot(Container $container): void
    {
        $service = $container->get(NotificationService::class);

        $container->get(RestRegistrar::class)->add(new NotificationController($service));

        add_action(
            'zadora_lms_certificate_issued',
            static function (array $certificate, array $course, int $userId) use ($service): void {
                $service->create([
                    'user_id' => $userId,
                    'type' => 'certificate_issued',
                    'title' => 'Certificate issued',
                    'body' => sprintf('Your certificate for %s is ready.', $course['title'] ?? 'your course'),
                    'data' => [
                        'certificate_id' => $certificate['id'] ?? null,
                        'course_id' => $course['id'] ?? null,
                    ],
                ]);
            },
            10,
            3
        );
    }
}
