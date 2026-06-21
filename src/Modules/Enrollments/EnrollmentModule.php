<?php

declare(strict_types=1);

namespace Zadora\Lms\Modules\Enrollments;

use Zadora\Lms\Core\ModuleInterface;
use Zadora\Lms\Database\TableNames;
use Zadora\Lms\Http\RestRegistrar;
use Zadora\Lms\Support\Clock;
use Zadora\Lms\Support\Container;

final class EnrollmentModule implements ModuleInterface
{
    public function register(Container $container): void
    {
        $container->singleton(EnrollmentRepository::class, static fn (): EnrollmentRepository => new EnrollmentRepository(TableNames::fromWordPress()));
        $container->singleton(EnrollmentService::class, static fn (Container $container): EnrollmentService => new EnrollmentService(
            $container->get(EnrollmentRepository::class),
            new Clock()
        ));
    }

    public function boot(Container $container): void
    {
        $service = $container->get(EnrollmentService::class);

        $container->get(RestRegistrar::class)->add(new EnrollmentController($service));

        add_action(
            'zadora_lms_order_paid',
            static function (array $order) use ($service): void {
                if (empty($order['course_id']) || empty($order['user_id'])) {
                    return;
                }

                $service->enroll([
                    'course_id' => (int) $order['course_id'],
                    'user_id' => (int) $order['user_id'],
                    'company_id' => ! empty($order['company_id']) ? (int) $order['company_id'] : null,
                    'source' => 'purchase',
                ]);
            }
        );
    }
}
