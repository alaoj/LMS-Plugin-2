<?php

declare(strict_types=1);

namespace Zadora\Lms\Modules\Notifications;

use InvalidArgumentException;
use Zadora\Lms\Support\Clock;

final class NotificationService
{
    public const TYPES = [
        'course_assigned',
        'course_completed',
        'assessment_graded',
        'certificate_issued',
        'certificate_expiring',
        'announcement',
    ];

    public function __construct(
        private readonly NotificationRepository $notifications,
        private readonly Clock $clock
    ) {
    }

    public function listForUser(int $userId, bool $unreadOnly = false): array
    {
        return array_map([$this, 'transform'], $this->notifications->listForUser($userId, $unreadOnly));
    }

    public function create(array $input): array
    {
        $userId = absint($input['user_id'] ?? 0);
        $type = sanitize_key((string) ($input['type'] ?? ''));
        $title = sanitize_text_field((string) ($input['title'] ?? ''));

        if (! $userId || $type === '' || $title === '') {
            throw new InvalidArgumentException('Notification user, type, and title are required.');
        }

        if (! in_array($type, self::TYPES, true)) {
            throw new InvalidArgumentException('Invalid notification type.');
        }

        $now = $this->clock->now();
        $id = $this->notifications->create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'body' => isset($input['body']) ? sanitize_textarea_field((string) $input['body']) : null,
            'data_json' => wp_json_encode($input['data'] ?? [], JSON_THROW_ON_ERROR),
            'read_at' => null,
            'created_at' => $now,
        ]);

        return [
            'id' => $id,
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'read_at' => null,
            'created_at' => $now,
        ];
    }

    public function markRead(int $id, int $userId): bool
    {
        return $this->notifications->markRead($id, $userId, $this->clock->now());
    }

    public function markAllRead(int $userId): bool
    {
        return $this->notifications->markAllRead($userId, $this->clock->now());
    }

    private function transform(array $notification): array
    {
        return [
            'id' => (int) $notification['id'],
            'user_id' => (int) $notification['user_id'],
            'type' => $notification['type'],
            'title' => $notification['title'],
            'body' => $notification['body'],
            'data' => $notification['data_json'] ? json_decode((string) $notification['data_json'], true) : [],
            'read_at' => $notification['read_at'],
            'created_at' => $notification['created_at'],
        ];
    }
}
