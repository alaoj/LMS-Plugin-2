<?php

declare(strict_types=1);

namespace Zadora\Lms\Modules\Notifications;

use Zadora\Lms\Database\TableNames;

final class NotificationRepository
{
    public function __construct(private readonly TableNames $tables)
    {
    }

    public function listForUser(int $userId, bool $unreadOnly = false): array
    {
        global $wpdb;

        $where = 'user_id = %d';

        if ($unreadOnly) {
            $where .= ' AND read_at IS NULL';
        }

        return $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$this->tables->notifications()} WHERE {$where} ORDER BY created_at DESC LIMIT 100", $userId),
            ARRAY_A
        ) ?: [];
    }

    public function create(array $data): int
    {
        global $wpdb;

        $wpdb->insert($this->tables->notifications(), $data);

        return (int) $wpdb->insert_id;
    }

    public function markRead(int $id, int $userId, string $readAt): bool
    {
        global $wpdb;

        return $wpdb->update(
            $this->tables->notifications(),
            ['read_at' => $readAt],
            ['id' => $id, 'user_id' => $userId]
        ) !== false;
    }

    public function markAllRead(int $userId, string $readAt): bool
    {
        global $wpdb;

        return $wpdb->query($wpdb->prepare(
            "UPDATE {$this->tables->notifications()} SET read_at = %s WHERE user_id = %d AND read_at IS NULL",
            $readAt,
            $userId
        )) !== false;
    }
}
