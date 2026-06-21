<?php

declare(strict_types=1);

namespace Zadora\Lms\Modules\Courses;

use Zadora\Lms\Database\TableNames;

final class LessonRepository
{
    public function __construct(private readonly TableNames $tables)
    {
    }

    public function listForCourse(int $courseId): array
    {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->tables->lessons()} WHERE course_id = %d ORDER BY sort_order ASC, id ASC",
                $courseId
            ),
            ARRAY_A
        ) ?: [];
    }

    public function find(int $id): ?array
    {
        global $wpdb;

        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->tables->lessons()} WHERE id = %d LIMIT 1", $id),
            ARRAY_A
        );

        return $row ?: null;
    }

    public function create(array $data): int
    {
        global $wpdb;

        $wpdb->insert($this->tables->lessons(), $data);

        return (int) $wpdb->insert_id;
    }

    public function update(int $id, array $data): bool
    {
        global $wpdb;

        return $wpdb->update($this->tables->lessons(), $data, ['id' => $id]) !== false;
    }

    public function countForCourse(int $courseId): int
    {
        global $wpdb;

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tables->lessons()} WHERE course_id = %d",
            $courseId
        ));
    }
}
