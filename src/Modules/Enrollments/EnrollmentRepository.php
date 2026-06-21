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

        $existingId = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->tables->enrollments()} WHERE course_id = %d AND user_id = %d LIMIT 1",
            $data['course_id'],
            $data['user_id']
        ));

        if ($existingId > 0) {
            $wpdb->update($this->tables->enrollments(), [
                'status' => 'active',
                'updated_at' => $data['updated_at'],
            ], ['id' => $existingId]);

            return $existingId;
        }

        $wpdb->insert($this->tables->enrollments(), $data);

        return (int) $wpdb->insert_id;
    }

    public function update(int $id, array $data): bool
    {
        global $wpdb;

        return $wpdb->update($this->tables->enrollments(), $data, ['id' => $id]) !== false;
    }
}
