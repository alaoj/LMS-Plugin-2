<?php

declare(strict_types=1);

namespace Zadora\Lms\Modules\Groups;

use Zadora\Lms\Database\TableNames;

final class GroupRepository
{
    public function __construct(private readonly TableNames $tables)
    {
    }

    public function list(array $filters = []): array
    {
        global $wpdb;

        $where = ['1=1'];
        $values = [];

        if (! empty($filters['company_id'])) {
            $where[] = 'company_id = %d';
            $values[] = absint($filters['company_id']);
        }

        if (! empty($filters['user_id'])) {
            $where[] = "id IN (SELECT group_id FROM {$this->tables->groupMembers()} WHERE user_id = %d AND status = 'active')";
            $values[] = absint($filters['user_id']);
        }

        $sql = "SELECT * FROM {$this->tables->groups()} WHERE " . implode(' AND ', $where) . ' ORDER BY created_at DESC LIMIT 100';

        if ($values) {
            $sql = $wpdb->prepare($sql, $values);
        }

        return $wpdb->get_results($sql, ARRAY_A) ?: [];
    }

    public function find(int $id): ?array
    {
        global $wpdb;

        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->tables->groups()} WHERE id = %d LIMIT 1", $id),
            ARRAY_A
        );

        return $row ?: null;
    }

    public function create(array $data): int
    {
        global $wpdb;

        $wpdb->insert($this->tables->groups(), $data);

        return (int) $wpdb->insert_id;
    }

    public function update(int $id, array $data): bool
    {
        global $wpdb;

        return $wpdb->update($this->tables->groups(), $data, ['id' => $id]) !== false;
    }

    public function upsertMember(array $data): int
    {
        global $wpdb;

        $existingId = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->tables->groupMembers()} WHERE group_id = %d AND user_id = %d LIMIT 1",
            $data['group_id'],
            $data['user_id']
        ));

        if ($existingId > 0) {
            $wpdb->update($this->tables->groupMembers(), [
                'role' => $data['role'],
                'status' => 'active',
                'updated_at' => $data['updated_at'],
            ], ['id' => $existingId]);

            return $existingId;
        }

        $wpdb->insert($this->tables->groupMembers(), $data);

        return (int) $wpdb->insert_id;
    }

    public function listMembers(int $groupId): array
    {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$this->tables->groupMembers()} WHERE group_id = %d ORDER BY created_at DESC", $groupId),
            ARRAY_A
        ) ?: [];
    }

    public function assignCourse(array $data): int
    {
        global $wpdb;

        $existingId = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->tables->groupCourses()} WHERE group_id = %d AND course_id = %d LIMIT 1",
            $data['group_id'],
            $data['course_id']
        ));

        if ($existingId > 0) {
            return $existingId;
        }

        $wpdb->insert($this->tables->groupCourses(), $data);

        return (int) $wpdb->insert_id;
    }

    public function metrics(int $groupId): array
    {
        global $wpdb;

        return [
            'members' => (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->tables->groupMembers()} WHERE group_id = %d AND status = 'active'",
                $groupId
            )),
            'courses' => (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->tables->groupCourses()} WHERE group_id = %d",
                $groupId
            )),
        ];
    }
}
