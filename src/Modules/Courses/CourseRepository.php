<?php

declare(strict_types=1);

namespace Zadora\Lms\Modules\Courses;

use Zadora\Lms\Database\TableNames;

final class CourseRepository
{
    public function __construct(private readonly TableNames $tables)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function list(array $filters = []): array
    {
        global $wpdb;

        $table = $this->tables->courses();
        $where = ['1=1'];
        $values = [];

        if (! empty($filters['status'])) {
            $where[] = 'status = %s';
            $values[] = $filters['status'];
        }

        if (! empty($filters['visibility'])) {
            $where[] = 'visibility = %s';
            $values[] = $filters['visibility'];
        }

        $sql = "SELECT * FROM {$table} WHERE " . implode(' AND ', $where) . ' ORDER BY created_at DESC LIMIT 100';

        if ($values) {
            $sql = $wpdb->prepare($sql, $values);
        }

        return $wpdb->get_results($sql, ARRAY_A) ?: [];
    }

    public function find(int $id): ?array
    {
        global $wpdb;

        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->tables->courses()} WHERE id = %d LIMIT 1", $id),
            ARRAY_A
        );

        return $row ?: null;
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): int
    {
        global $wpdb;

        $wpdb->insert($this->tables->courses(), $data);

        return (int) $wpdb->insert_id;
    }

    /** @param array<string, mixed> $data */
    public function update(int $id, array $data): bool
    {
        global $wpdb;

        return $wpdb->update($this->tables->courses(), $data, ['id' => $id]) !== false;
    }

    public function joinWaitlist(int $courseId, int $userId, string $joinedAt): int
    {
        global $wpdb;

        $existingId = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->tables->waitlists()} WHERE course_id = %d AND user_id = %d LIMIT 1",
            $courseId,
            $userId
        ));

        if ($existingId > 0) {
            return $existingId;
        }

        $wpdb->insert($this->tables->waitlists(), [
            'course_id' => $courseId,
            'user_id' => $userId,
            'status' => 'joined',
            'joined_at' => $joinedAt,
            'notified_at' => null,
        ]);

        return (int) $wpdb->insert_id;
    }

    public function waitlistCount(int $courseId): int
    {
        global $wpdb;

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tables->waitlists()} WHERE course_id = %d AND status = 'joined'",
            $courseId
        ));
    }
}
