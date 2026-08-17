<?php

declare(strict_types=1);

namespace Zadora\Lms\Modules\Announcements;

use Zadora\Lms\Database\TableNames;

final class AnnouncementRepository
{
    public function __construct(private readonly TableNames $tables)
    {
    }

    public function list(array $filters = []): array
    {
        global $wpdb;

        $where = ["status = 'published'"];
        $values = [];

        if (! empty($filters['scope_type'])) {
            $where[] = 'scope_type = %s';
            $values[] = sanitize_key((string) $filters['scope_type']);
        }

        if (! empty($filters['scope_id'])) {
            $where[] = 'scope_id = %d';
            $values[] = absint($filters['scope_id']);
        }

        $where[] = '(starts_at IS NULL OR starts_at <= UTC_TIMESTAMP())';
        $where[] = '(ends_at IS NULL OR ends_at >= UTC_TIMESTAMP())';

        $sql = "SELECT * FROM {$this->tables->announcements()} WHERE " . implode(' AND ', $where) . ' ORDER BY created_at DESC LIMIT 100';

        if ($values) {
            $sql = $wpdb->prepare($sql, $values);
        }

        return $wpdb->get_results($sql, ARRAY_A) ?: [];
    }

    public function find(int $id): ?array
    {
        global $wpdb;

        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->tables->announcements()} WHERE id = %d LIMIT 1", $id),
            ARRAY_A
        );

        return $row ?: null;
    }

    public function create(array $data): int
    {
        global $wpdb;

        $wpdb->insert($this->tables->announcements(), $data);

        return (int) $wpdb->insert_id;
    }

    public function update(int $id, array $data): bool
    {
        global $wpdb;

        return $wpdb->update($this->tables->announcements(), $data, ['id' => $id]) !== false;
    }
}
