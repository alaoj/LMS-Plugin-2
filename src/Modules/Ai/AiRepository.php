<?php

declare(strict_types=1);

namespace Zadora\Lms\Modules\Ai;

use Zadora\Lms\Database\TableNames;

final class AiRepository
{
    public function __construct(private readonly TableNames $tables)
    {
    }

    public function log(array $data): int
    {
        global $wpdb;

        $wpdb->insert($this->tables->aiRequests(), $data);

        return (int) $wpdb->insert_id;
    }

    public function countForUserThisMonth(int $userId): int
    {
        global $wpdb;

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tables->aiRequests()} WHERE user_id = %d AND created_at >= %s",
            $userId,
            gmdate('Y-m-01 00:00:00')
        ));
    }
}
