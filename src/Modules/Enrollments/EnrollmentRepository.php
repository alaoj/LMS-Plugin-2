<?php

declare(strict_types=1);

namespace Zadora\Lms\Modules\Enrollments;

use Zadora\Lms\Database\TableNames;

final class EnrollmentRepository
{
    public function __construct(private readonly TableNames $tables)
    {
    }

    public function listForUser(int $userId): array
    {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$this->tables->enrollments()} WHERE user_id = %d ORDER BY created_at DESC", $userId),
            ARRAY_A
        ) ?: [];
    }

    public function find(int $id): ?array
    {
        global $wpdb;

        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->tables->enrollments()} WHERE id = %d LIMIT 1", $id),
            ARRAY_A
        );

        return $row ?: null;
    }

    public function create(array $data): int
    {
        global $wpdb;

        $wpdb->insert($this->tables->enrollments(), $data);

        return (int) $wpdb->insert_id;
    }

    public function update(int $id, array $data): bool
    {
        global $wpdb;

        return $wpdb->update($this->tables->enrollments(), $data, ['id' => $id]) !== false;
    }
}
