<?php

declare(strict_types=1);

namespace Zadora\Lms\Modules\Reporting;

use Zadora\Lms\Database\TableNames;

final class ReportingRepository
{
    public function __construct(private readonly TableNames $tables)
    {
    }

    public function overview(): array
    {
        global $wpdb;

        return [
            'courses' => $this->count($this->tables->courses()),
            'enrollments' => $this->count($this->tables->enrollments()),
            'completed_enrollments' => $this->countWhere($this->tables->enrollments(), "status = 'completed'"),
            'assessments' => $this->count($this->tables->assessments()),
            'submissions_pending_grading' => $this->countWhere($this->tables->submissions(), "status = 'submitted'"),
            'submissions_graded' => $this->countWhere($this->tables->submissions(), "status = 'graded'"),
            'certificates' => $this->count($this->tables->certificates()),
            'companies' => $this->count($this->tables->companies()),
            'orders' => $this->count($this->tables->orders()),
            'revenue' => (float) $wpdb->get_var("SELECT COALESCE(SUM(amount), 0) FROM {$this->tables->orders()} WHERE status = 'paid'"),
        ];
    }

    public function corporate(int $companyId): array
    {
        global $wpdb;

        $employees = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tables->companyUsers()} WHERE company_id = %d AND status = 'active'",
            $companyId
        ));

        $enrollments = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tables->enrollments()} WHERE company_id = %d",
            $companyId
        ));

        $completed = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tables->enrollments()} WHERE company_id = %d AND status = 'completed'",
            $companyId
        ));

        return [
            'company_id' => $companyId,
            'employees' => $employees,
            'enrollments' => $enrollments,
            'completed_enrollments' => $completed,
            'compliance_percentage' => $enrollments > 0 ? round(($completed / $enrollments) * 100, 2) : 0,
        ];
    }

    public function learner(int $userId): array
    {
        global $wpdb;

        $enrollments = $wpdb->get_results($wpdb->prepare(
            "SELECT e.*, c.title AS course_title
             FROM {$this->tables->enrollments()} e
             LEFT JOIN {$this->tables->courses()} c ON c.id = e.course_id
             WHERE e.user_id = %d
             ORDER BY e.updated_at DESC
             LIMIT 20",
            $userId
        ), ARRAY_A) ?: [];

        $items = array_map(function (array $enrollment) use ($wpdb): array {
            $totalLessons = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->tables->lessons()} WHERE course_id = %d",
                (int) $enrollment['course_id']
            ));

            $completedLessons = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->tables->progress()} WHERE enrollment_id = %d AND status = 'completed'",
                (int) $enrollment['id']
            ));

            return [
                'enrollment_id' => (int) $enrollment['id'],
                'course_id' => (int) $enrollment['course_id'],
                'course_title' => $enrollment['course_title'] ?: 'Untitled course',
                'status' => $enrollment['status'],
                'started_at' => $enrollment['started_at'],
                'completed_at' => $enrollment['completed_at'],
                'completed_lessons' => $completedLessons,
                'total_lessons' => $totalLessons,
                'progress_percent' => $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100, 2) : 0,
            ];
        }, $enrollments);

        $completed = array_filter($items, static fn (array $item): bool => $item['status'] === 'completed');

        return [
            'user_id' => $userId,
            'active_courses' => count(array_filter($items, static fn (array $item): bool => $item['status'] === 'active')),
            'completed_courses' => count($completed),
            'courses' => $items,
        ];
    }

    public function assessments(): array
    {
        return [
            'total_assessments' => $this->count($this->tables->assessments()),
            'pending_grading' => $this->countWhere($this->tables->submissions(), "status = 'submitted'"),
            'graded' => $this->countWhere($this->tables->submissions(), "status = 'graded'"),
            'returned' => $this->countWhere($this->tables->submissions(), "status = 'returned'"),
        ];
    }

    private function count(string $table): int
    {
        global $wpdb;

        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
    }

    private function countWhere(string $table, string $where): int
    {
        global $wpdb;

        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE {$where}");
    }
}
