<?php

declare(strict_types=1);

namespace Zadora\Lms\Modules\Payments;

use InvalidArgumentException;
use Zadora\Lms\Support\Clock;

final class PaymentService
{
    public const GATEWAYS = ['stripe', 'paystack'];
    public const CURRENCIES = ['NGN', 'USD', 'GBP', 'EUR'];

    public function __construct(
        private readonly OrderRepository $orders,
        private readonly Clock $clock
    ) {
    }

    public function createOrder(array $input, int $userId): array
    {
        $amount = (float) ($input['amount'] ?? 0);
        $currency = strtoupper(sanitize_text_field((string) ($input['currency'] ?? '')));
        $gateway = sanitize_key((string) ($input['gateway'] ?? ''));

        if ($amount <= 0) {
            throw new InvalidArgumentException('Order amount must be greater than zero.');
        }

        if (! in_array($currency, self::CURRENCIES, true)) {
            throw new InvalidArgumentException('Unsupported currency.');
        }

        if (! in_array($gateway, self::GATEWAYS, true)) {
            throw new InvalidArgumentException('Unsupported payment gateway.');
        }

        $now = $this->clock->now();
        $reference = sprintf('zd_%s_%s', $gateway, wp_generate_uuid4());
        $id = $this->orders->createOrder([
            'user_id' => $userId,
            'course_id' => ! empty($input['course_id']) ? absint($input['course_id']) : null,
            'company_id' => ! empty($input['company_id']) ? absint($input['company_id']) : null,
            'status' => 'pending',
            'amount' => $amount,
            'currency' => $currency,
            'gateway' => $gateway,
            'gateway_reference' => $reference,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $this->transformOrder($this->orders->findOrder($id) ?: []);
    }

    public function captureWebhook(string $gateway, array $payload): array
    {
        $gateway = sanitize_key($gateway);

        if (! in_array($gateway, self::GATEWAYS, true)) {
            throw new InvalidArgumentException('Unsupported payment gateway.');
        }

        $reference = sanitize_text_field((string) ($payload['reference'] ?? $payload['id'] ?? ''));
        $status = sanitize_key((string) ($payload['status'] ?? ''));

        if ($reference === '' || $status === '') {
            throw new InvalidArgumentException('Webhook reference and status are required.');
        }

        $now = $this->clock->now();
        $paymentStatus = in_array($status, ['paid', 'succeeded', 'success'], true) ? 'paid' : 'failed';

        $this->orders->updateOrderByReference($reference, [
            'status' => $paymentStatus,
            'updated_at' => $now,
        ]);

        $this->orders->recordPayment([
            'order_id' => absint($payload['order_id'] ?? 0),
            'gateway' => $gateway,
            'gateway_reference' => $reference,
            'status' => $paymentStatus,
            'amount' => (float) ($payload['amount'] ?? 0),
            'currency' => strtoupper(sanitize_text_field((string) ($payload['currency'] ?? 'USD'))),
            'payload_json' => wp_json_encode($payload, JSON_THROW_ON_ERROR),
            'paid_at' => $paymentStatus === 'paid' ? $now : null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return ['received' => true, 'status' => $paymentStatus];
    }

    private function transformOrder(array $order): array
    {
        return [
            'id' => (int) ($order['id'] ?? 0),
            'user_id' => (int) ($order['user_id'] ?? 0),
            'course_id' => ! empty($order['course_id']) ? (int) $order['course_id'] : null,
            'company_id' => ! empty($order['company_id']) ? (int) $order['company_id'] : null,
            'status' => $order['status'] ?? '',
            'amount' => isset($order['amount']) ? (float) $order['amount'] : 0,
            'currency' => $order['currency'] ?? '',
            'gateway' => $order['gateway'] ?? '',
            'gateway_reference' => $order['gateway_reference'] ?? '',
        ];
    }
}
