<?php

declare(strict_types=1);

namespace Zadora\Lms\Modules\Announcements;

use InvalidArgumentException;
use Zadora\Lms\Support\Arr;
use Zadora\Lms\Support\Clock;

final class AnnouncementService
{
    public const SCOPES = ['global', 'course', 'group', 'company'];
    public const STATUSES = ['draft', 'published', 'archived'];

    public function __construct(
        private readonly AnnouncementRepository $announcements,
        private readonly Clock $clock
    ) {
    }

    public function list(array $filters = []): array
    {
        return array_map([$this, 'transform'], $this->announcements->list($filters));
    }

    public function create(array $input, int $authorId): array
    {
        $data = $this->validate($input);
        $now = $this->clock->now();

        $data['author_id'] = $authorId;
        $data['created_at'] = $now;
        $data['updated_at'] = $now;

        $id = $this->announcements->create($data);
        $announcement = $this->announcements->find($id) ?: [];

        if (($announcement['status'] ?? '') === 'published') {
            do_action('zadora_lms_announcement_published', $this->transform($announcement));
        }

        return $this->transform($announcement);
    }

    public function update(int $id, array $input): ?array
    {
        if (! $this->announcements->find($id)) {
            return null;
        }

        $data = $this->validate($input, false);
        $data['updated_at'] = $this->clock->now();

        $this->announcements->update($id, $data);

        return $this->transform($this->announcements->find($id) ?: []);
    }

    private function validate(array $input, bool $creating = true): array
    {
        $data = Arr::only($input, ['scope_type', 'scope_id', 'title', 'body', 'status', 'starts_at', 'ends_at']);

        if ($creating && (empty($data['title']) || empty($data['body']))) {
            throw new InvalidArgumentException('Announcement title and body are required.');
        }

        if ($creating) {
            $data['scope_type'] = $data['scope_type'] ?? 'global';
            $data['status'] = $data['status'] ?? 'published';
        }

        if (isset($data['scope_type'])) {
            $data['scope_type'] = sanitize_key((string) $data['scope_type']);

            if (! in_array($data['scope_type'], self::SCOPES, true)) {
                throw new InvalidArgumentException('Invalid announcement scope.');
            }
        }

        if (isset($data['scope_id'])) {
            $data['scope_id'] = absint($data['scope_id']) ?: null;
        }

        if (isset($data['title'])) {
            $data['title'] = sanitize_text_field((string) $data['title']);
        }

        if (isset($data['body'])) {
            $data['body'] = sanitize_textarea_field((string) $data['body']);
        }

        if (isset($data['status'])) {
            $data['status'] = sanitize_key((string) $data['status']);

            if (! in_array($data['status'], self::STATUSES, true)) {
                throw new InvalidArgumentException('Invalid announcement status.');
            }
        }

        foreach (['starts_at', 'ends_at'] as $dateField) {
            if (array_key_exists($dateField, $data)) {
                $data[$dateField] = $data[$dateField] ? sanitize_text_field((string) $data[$dateField]) : null;
            }
        }

        return $data;
    }

    private function transform(array $announcement): array
    {
        return [
            'id' => (int) ($announcement['id'] ?? 0),
            'scope_type' => $announcement['scope_type'] ?? 'global',
            'scope_id' => ! empty($announcement['scope_id']) ? (int) $announcement['scope_id'] : null,
            'author_id' => (int) ($announcement['author_id'] ?? 0),
            'title' => $announcement['title'] ?? '',
            'body' => $announcement['body'] ?? '',
            'status' => $announcement['status'] ?? '',
            'starts_at' => $announcement['starts_at'] ?? null,
            'ends_at' => $announcement['ends_at'] ?? null,
            'created_at' => $announcement['created_at'] ?? null,
            'updated_at' => $announcement['updated_at'] ?? null,
        ];
    }
}
