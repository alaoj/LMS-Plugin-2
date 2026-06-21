<?php

declare(strict_types=1);

namespace Zadora\Lms\Modules\Companies;

use Zadora\Lms\Database\TableNames;

final class CompanyRepository
{
    public function __construct(private readonly TableNames $tables)
    {
    }

    public function list(): array
    {
        global $wpdb;

        return $wpdb->get_results("SELECT * FROM {$this->tables->companies()} ORDER BY created_at DESC LIMIT 100", ARRAY_A) ?: [];
    }

    public function find(int $id): ?array
    {
        global $wpdb;

        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->tables->companies()} WHERE id = %d LIMIT 1", $id),
            ARRAY_A
        );

        return $row ?: null;
    }

    public function create(array $data): int
    {
        global $wpdb;

        $wpdb->insert($this->tables->companies(), $data);

        return (int) $wpdb->insert_id;
    }

    public function update(int $id, array $data): bool
    {
        global $wpdb;

        return $wpdb->update($this->tables->companies(), $data, ['id' => $id]) !== false;
    }

    public function createDepartment(array $data): int
    {
        global $wpdb;

        $wpdb->insert($this->tables->departments(), $data);

        return (int) $wpdb->insert_id;
    }

    public function addUser(array $data): int
    {
        global $wpdb;

        $wpdb->insert($this->tables->companyUsers(), $data);

        return (int) $wpdb->insert_id;
    }

    public function companyMetrics(int $companyId): array
    {
        global $wpdb;

        $users = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tables->companyUsers()} WHERE company_id = %d AND status = 'active'",
            $companyId
        ));

        $departments = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tables->departments()} WHERE company_id = %d",
            $companyId
        ));

        return [
            'active_users' => $users,
            'departments' => $departments,
        ];
    }
}
