<?php

declare(strict_types=1);

namespace Zadora\Lms\Modules\Courses;

use InvalidArgumentException;
use Zadora\Lms\Support\Arr;
use Zadora\Lms\Support\Clock;

final class CourseService
{
    public const STATUSES = ['draft', 'coming_soon', 'open', 'private', 'closed', 'archived'];
    public const VISIBILITIES = ['public', 'private', 'company', 'group'];

    public function __construct(
        private readonly CourseRepository $courses,
        private readonly Clock $clock
    ) {
    }

    public function list(array $filters = []): array
    {
        return array_map([$this, 'transform'], $this->courses->list($filters));
    }

    public function find(int $id): ?array
    {
        $course = $this->courses->find($id);

        return $course ? $this->transform($course) : null;
    }

    public function create(array $input, int $authorId): array
    {
        $data = $this->validate($input);
        $now = $this->clock->now();

        $data['author_id'] = $authorId;
        $data['slug'] = sanitize_title($data['slug'] ?: $data['title']);
        $data['created_at'] = $now;
        $data['updated_at'] = $now;

        $id = $this->courses->create($data);

        return $this->find($id) ?: [];
    }

    public function update(int $id, array $input): ?array
    {
        $existing = $this->courses->find($id);

        if (! $existing) {
            return null;
        }

        $data = $this->validate($input, false);
        $data['updated_at'] = $this->clock->now();

        if (isset($data['slug'])) {
            $data['slug'] = sanitize_title((string) $data['slug']);
        }

        $this->courses->update($id, $data);

        return $this->find($id);
    }

    public function joinWaitlist(int $courseId, int $userId): array
    {
        $course = $this->courses->find($courseId);

        if (! $course) {
            throw new InvalidArgumentException('Course not found.');
        }

        if ($course['status'] !== 'coming_soon') {
            throw new InvalidArgumentException('Only coming soon courses support waitlists.');
        }

        $id = $this->courses->joinWaitlist($courseId, $userId, $this->clock->now());

        do_action('zadora_lms_course_waitlist_joined', $courseId, $userId, $id);

        return [
            'id' => $id,
            'course_id' => $courseId,
            'user_id' => $userId,
            'waitlist_count' => $this->courses->waitlistCount($courseId),
        ];
    }

    private function validate(array $input, bool $creating = true): array
    {
        $data = Arr::only($input, [
            'title',
            'slug',
            'summary',
            'description',
            'status',
            'visibility',
            'price_amount',
            'currency',
            'certificate_template_id',
            'company_id',
        ]);

        if ($creating && empty($data['title'])) {
            throw new InvalidArgumentException('Course title is required.');
        }

        if (isset($data['title'])) {
            $data['title'] = sanitize_text_field((string) $data['title']);
        }

        if (isset($data['summary'])) {
            $data['summary'] = sanitize_textarea_field((string) $data['summary']);
        }

        if (isset($data['description'])) {
            $data['description'] = wp_kses_post((string) $data['description']);
        }

        if ($creating) {
            $data['status'] = $data['status'] ?? 'draft';
            $data['visibility'] = $data['visibility'] ?? 'public';
        }

        if ($creating) {
            $data['slug'] = isset($data['slug']) ? sanitize_title((string) $data['slug']) : '';
        } elseif (array_key_exists('slug', $data)) {
            $data['slug'] = sanitize_title((string) $data['slug']);
        }

        if (isset($data['status']) && ! in_array($data['status'], self::STATUSES, true)) {
            throw new InvalidArgumentException('Invalid course status.');
        }

        if (isset($data['visibility']) && ! in_array($data['visibility'], self::VISIBILITIES, true)) {
            throw new InvalidArgumentException('Invalid course visibility.');
        }

        if (isset($data['currency'])) {
            $data['currency'] = strtoupper(sanitize_text_field((string) $data['currency']));
        }

        return $data;
    }

    private function transform(array $course): array
    {
        return [
            'id' => (int) $course['id'],
            'title' => $course['title'],
            'slug' => $course['slug'],
            'summary' => $course['summary'],
            'description' => $course['description'],
            'status' => $course['status'],
            'visibility' => $course['visibility'],
            'price_amount' => isset($course['price_amount']) ? (float) $course['price_amount'] : null,
            'currency' => $course['currency'],
            'certificate_template_id' => $course['certificate_template_id'] ? (int) $course['certificate_template_id'] : null,
            'waitlist_count' => $this->courses->waitlistCount((int) $course['id']),
            'created_at' => $course['created_at'],
            'updated_at' => $course['updated_at'],
        ];
    }
}
