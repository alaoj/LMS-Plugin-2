<?php

declare(strict_types=1);

namespace Zadora\Lms\Modules\Certificates;

use Zadora\Lms\Database\TableNames;

final class CertificateRepository
{
    public function __construct(private readonly TableNames $tables)
    {
    }

    public function listForUser(int $userId): array
    {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$this->tables->certificates()} WHERE user_id = %d ORDER BY issued_at DESC", $userId),
            ARRAY_A
        ) ?: [];
    }

    public function findByHash(string $hash): ?array
    {
        global $wpdb;

        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->tables->certificates()} WHERE verification_hash = %s LIMIT 1", $hash),
            ARRAY_A
        );

        return $row ?: null;
    }

    public function create(array $data): int
    {
        global $wpdb;

        $wpdb->insert($this->tables->certificates(), $data);

        return (int) $wpdb->insert_id;
    }
}
