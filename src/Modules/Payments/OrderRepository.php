<?php

declare(strict_types=1);

namespace Zadora\Lms\Modules\Payments;

use Zadora\Lms\Database\TableNames;

final class OrderRepository
{
    public function __construct(private readonly TableNames $tables)
    {
    }

    public function createOrder(array $data): int
    {
        global $wpdb;

        $wpdb->insert($this->tables->orders(), $data);

        return (int) $wpdb->insert_id;
    }

    public function findOrder(int $id): ?array
    {
        global $wpdb;

        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->tables->orders()} WHERE id = %d LIMIT 1", $id),
            ARRAY_A
        );

        return $row ?: null;
    }

    public function updateOrderByReference(string $reference, array $data): bool
    {
        global $wpdb;

        return $wpdb->update($this->tables->orders(), $data, ['gateway_reference' => $reference]) !== false;
    }

    public function findOrderByReference(string $reference): ?array
    {
        global $wpdb;

        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->tables->orders()} WHERE gateway_reference = %s LIMIT 1", $reference),
            ARRAY_A
        );

        return $row ?: null;
    }

    public function recordPayment(array $data): int
    {
        global $wpdb;

        $existingId = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->tables->payments()} WHERE gateway_reference = %s LIMIT 1",
            $data['gateway_reference']
        ));

        if ($existingId > 0) {
            return $existingId;
        }

        $wpdb->insert($this->tables->payments(), $data);

        return (int) $wpdb->insert_id;
    }
}
