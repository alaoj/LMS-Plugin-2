<?php

declare(strict_types=1);

namespace Zadora\Lms\Modules\Assessments;

use Zadora\Lms\Database\TableNames;

final class AssessmentRepository
{
    public function __construct(private readonly TableNames $tables)
    {
    }

    public function listForCourse(int $courseId): array
    {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$this->tables->assessments()} WHERE course_id = %d ORDER BY created_at ASC", $courseId),
            ARRAY_A
        ) ?: [];
    }

    public function find(int $id): ?array
    {
        global $wpdb;

        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->tables->assessments()} WHERE id = %d LIMIT 1", $id),
            ARRAY_A
        );

        return $row ?: null;
    }

    public function create(array $data): int
    {
        global $wpdb;

        $wpdb->insert($this->tables->assessments(), $data);

        return (int) $wpdb->insert_id;
    }

    public function update(int $id, array $data): bool
    {
        global $wpdb;

        return $wpdb->update($this->tables->assessments(), $data, ['id' => $id]) !== false;
    }

    public function createSubmission(array $data): int
    {
        global $wpdb;

        $wpdb->insert($this->tables->submissions(), $data);

        return (int) $wpdb->insert_id;
    }

    public function findSubmission(int $id): ?array
    {
        global $wpdb;

        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->tables->submissions()} WHERE id = %d LIMIT 1", $id),
            ARRAY_A
        );

        return $row ?: null;
    }

    public function listSubmissions(array $filters = []): array
    {
        global $wpdb;

        $where = ['1=1'];
        $values = [];

        if (! empty($filters['assessment_id'])) {
            $where[] = 'assessment_id = %d';
            $values[] = absint($filters['assessment_id']);
        }

        if (! empty($filters['user_id'])) {
            $where[] = 'user_id = %d';
            $values[] = absint($filters['user_id']);
        }

        if (! empty($filters['status'])) {
            $where[] = 'status = %s';
            $values[] = sanitize_key((string) $filters['status']);
        }

        $sql = "SELECT * FROM {$this->tables->submissions()} WHERE " . implode(' AND ', $where) . ' ORDER BY created_at DESC LIMIT 100';

        if ($values) {
            $sql = $wpdb->prepare($sql, $values);
        }

        return $wpdb->get_results($sql, ARRAY_A) ?: [];
    }

    public function gradeSubmission(int $id, array $data): bool
    {
        global $wpdb;

        return $wpdb->update($this->tables->submissions(), $data, ['id' => $id]) !== false;
    }

    public function userIsEnrolled(int $userId, int $courseId): bool
    {
        global $wpdb;

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tables->enrollments()} WHERE user_id = %d AND course_id = %d AND status IN ('active', 'completed')",
            $userId,
            $courseId
        )) > 0;
    }
}
