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
