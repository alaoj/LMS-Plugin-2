<?php

declare(strict_types=1);

namespace Zadora\Lms\Modules\Enrollments;

use InvalidArgumentException;
use Zadora\Lms\Support\Clock;

final class EnrollmentService
{
    public const STATUSES = ['active', 'completed', 'cancelled', 'expired'];
    public const SOURCES = ['manual', 'purchase', 'company_assignment', 'self_enrollment'];

    public function __construct(
        private readonly EnrollmentRepository $enrollments,
        private readonly Clock $clock
    ) {
    }

    public function listForUser(int $userId): array
    {
        return array_map([$this, 'transform'], $this->enrollments->listForUser($userId));
    }

    public function enroll(array $input): array
    {
        $courseId = absint($input['course_id'] ?? 0);
        $userId = absint($input['user_id'] ?? get_current_user_id());
        $source = sanitize_key((string) ($input['source'] ?? 'manual'));

        if (! $courseId || ! $userId) {
            throw new InvalidArgumentException('Course and learner are required.');
        }

        if (! in_array($source, self::SOURCES, true)) {
            throw new InvalidArgumentException('Invalid enrollment source.');
        }

        $now = $this->clock->now();
        $id = $this->enrollments->create([
            'course_id' => $courseId,
            'user_id' => $userId,
            'company_id' => ! empty($input['company_id']) ? absint($input['company_id']) : null,
            'status' => 'active',
            'source' => $source,
            'started_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $this->transform($this->enrollments->find($id) ?: []);
    }

    public function complete(int $id): ?array
    {
        $enrollment = $this->enrollments->find($id);

        if (! $enrollment) {
            return null;
        }

        $now = $this->clock->now();
        $this->enrollments->update($id, [
            'status' => 'completed',
            'completed_at' => $now,
            'updated_at' => $now,
        ]);

        return $this->transform($this->enrollments->find($id) ?: []);
    }

    private function transform(array $enrollment): array
    {
        return [
            'id' => (int) ($enrollment['id'] ?? 0),
            'course_id' => (int) ($enrollment['course_id'] ?? 0),
            'user_id' => (int) ($enrollment['user_id'] ?? 0),
            'company_id' => ! empty($enrollment['company_id']) ? (int) $enrollment['company_id'] : null,
            'status' => $enrollment['status'] ?? '',
            'source' => $enrollment['source'] ?? '',
            'started_at' => $enrollment['started_at'] ?? null,
            'completed_at' => $enrollment['completed_at'] ?? null,
        ];
    }
}
