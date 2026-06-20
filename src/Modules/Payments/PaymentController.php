<?php

declare(strict_types=1);

namespace Zadora\Lms\Modules\Payments;

use InvalidArgumentException;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use Zadora\Lms\Http\ControllerInterface;
use Zadora\Lms\Http\Permissions;
use Zadora\Lms\Http\RestRegistrar;

final class PaymentController implements ControllerInterface
{
    public function __construct(private readonly PaymentService $payments)
    {
    }

    public function registerRoutes(): void
    {
        register_rest_route(RestRegistrar::NAMESPACE, '/orders', [
            'methods' => 'POST',
            'callback' => [$this, 'createOrder'],
            'permission_callback' => Permissions::authenticated(),
        ]);

        register_rest_route(RestRegistrar::NAMESPACE, '/payments/(?P<gateway>stripe|paystack)/webhook', [
            'methods' => 'POST',
            'callback' => [$this, 'webhook'],
            'permission_callback' => Permissions::public(),
        ]);
    }

    public function createOrder(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        try {
            return new WP_REST_Response($this->payments->createOrder($request->get_json_params() ?: [], get_current_user_id()), 201);
        } catch (InvalidArgumentException $exception) {
            return new WP_Error('zadora_invalid_order', $exception->getMessage(), ['status' => 422]);
        }
    }

    public function webhook(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        try {
            return rest_ensure_response($this->payments->captureWebhook((string) $request['gateway'], $request->get_json_params() ?: []));
        } catch (InvalidArgumentException $exception) {
            return new WP_Error('zadora_invalid_payment_webhook', $exception->getMessage(), ['status' => 422]);
        }
    }
}
