<?php

declare(strict_types=1);

namespace Zadora\Lms\Modules\Courses;

use Zadora\Lms\Database\TableNames;

final class ProgressRepository
{
    public function __construct(private readonly TableNames $tables)
    {
    }

    public function findEnrollmentForUserAndCourse(int $userId, int $courseId): ?array
    {
        global $wpdb;

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->tables->enrollments()} WHERE user_id = %d AND course_id = %d LIMIT 1",
                $userId,
                $courseId
            ),
            ARRAY_A
        );

        return $row ?: null;
    }

    public function completedLessonsForEnrollment(int $enrollmentId): int
    {
        global $wpdb;

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tables->progress()} WHERE enrollment_id = %d AND status = 'completed'",
            $enrollmentId
        ));
    }

    public function upsertLessonProgress(array $data): void
    {
        global $wpdb;

        $existingId = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->tables->progress()} WHERE enrollment_id = %d AND lesson_id = %d LIMIT 1",
            $data['enrollment_id'],
            $data['lesson_id']
        ));

        if ($existingId > 0) {
            unset($data['created_at']);
            $wpdb->update($this->tables->progress(), $data, ['id' => $existingId]);

            return;
        }

        $wpdb->insert($this->tables->progress(), $data);
    }

    public function updateEnrollment(int $enrollmentId, array $data): bool
    {
        global $wpdb;

        return $wpdb->update($this->tables->enrollments(), $data, ['id' => $enrollmentId]) !== false;
    }

    public function listForEnrollment(int $enrollmentId): array
    {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$this->tables->progress()} WHERE enrollment_id = %d", $enrollmentId),
            ARRAY_A
        ) ?: [];
    }
}
