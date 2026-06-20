<?php

declare(strict_types=1);

namespace Zadora\Lms\Modules\Payments;

use Zadora\Lms\Core\ModuleInterface;
use Zadora\Lms\Database\TableNames;
use Zadora\Lms\Http\RestRegistrar;
use Zadora\Lms\Support\Clock;
use Zadora\Lms\Support\Container;

final class PaymentModule implements ModuleInterface
{
    public function register(Container $container): void
    {
        $container->singleton(OrderRepository::class, static fn (): OrderRepository => new OrderRepository(TableNames::fromWordPress()));
        $container->singleton(PaymentService::class, static fn (Container $container): PaymentService => new PaymentService(
            $container->get(OrderRepository::class),
            new Clock()
        ));
    }

    public function boot(Container $container): void
    {
        $container->get(RestRegistrar::class)->add(new PaymentController($container->get(PaymentService::class)));
    }
}
