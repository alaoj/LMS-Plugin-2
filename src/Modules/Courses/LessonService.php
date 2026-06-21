<?php

declare(strict_types=1);

namespace Zadora\Lms\Modules\Courses;

use InvalidArgumentException;
use Zadora\Lms\Support\Arr;
use Zadora\Lms\Support\Clock;

final class LessonService
{
    public function __construct(
        private readonly LessonRepository $lessons,
        private readonly ProgressRepository $progress,
        private readonly Clock $clock
    ) {
    }

    public function listForCourse(int $courseId, int $userId = 0): array
    {
        $lessonRows = $this->lessons->listForCourse($courseId);
        $progressRows = [];

        if ($userId > 0) {
            $enrollment = $this->progress->findEnrollmentForUserAndCourse($userId, $courseId);
            $progressRows = $enrollment ? $this->progress->listForEnrollment((int) $enrollment['id']) : [];
        }

        $progressByLesson = [];

        foreach ($progressRows as $row) {
            $progressByLesson[(int) $row['lesson_id']] = $row;
        }

        return array_map(
            fn (array $lesson): array => $this->transform($lesson, $progressByLesson[(int) $lesson['id']] ?? null),
            $lessonRows
        );
    }

    public function create(int $courseId, array $input): array
    {
        $data = $this->validate($input);
        $now = $this->clock->now();

        $data['course_id'] = $courseId;
        $data['slug'] = sanitize_title($data['slug'] ?: $data['title']);
        $data['created_at'] = $now;
        $data['updated_at'] = $now;

        $id = $this->lessons->create($data);

        return $this->transform($this->lessons->find($id) ?: []);
    }

    public function update(int $lessonId, array $input): ?array
    {
        if (! $this->lessons->find($lessonId)) {
            return null;
        }

        $data = $this->validate($input, false);
        $data['updated_at'] = $this->clock->now();

        if (isset($data['slug'])) {
            $data['slug'] = sanitize_title((string) $data['slug']);
        }

        $this->lessons->update($lessonId, $data);

        return $this->transform($this->lessons->find($lessonId) ?: []);
    }

    public function complete(int $lessonId, int $userId): ?array
    {
        $lesson = $this->lessons->find($lessonId);

        if (! $lesson) {
            return null;
        }

        $enrollment = $this->progress->findEnrollmentForUserAndCourse($userId, (int) $lesson['course_id']);

        if (! $enrollment) {
            throw new InvalidArgumentException('Learner is not enrolled in this course.');
        }

        $now = $this->clock->now();

        $this->progress->upsertLessonProgress([
            'enrollment_id' => (int) $enrollment['id'],
            'lesson_id' => $lessonId,
            'status' => 'completed',
            'progress_percent' => 100,
            'completed_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $totalLessons = $this->lessons->countForCourse((int) $lesson['course_id']);
        $completedLessons = $this->progress->completedLessonsForEnrollment((int) $enrollment['id']);
        $courseComplete = $totalLessons > 0 && $completedLessons >= $totalLessons;

        $this->progress->updateEnrollment((int) $enrollment['id'], [
            'status' => $courseComplete ? 'completed' : 'active',
            'completed_at' => $courseComplete ? $now : null,
            'updated_at' => $now,
        ]);

        if ($courseComplete) {
            do_action('zadora_lms_course_completed', (int) $lesson['course_id'], $userId, (int) $enrollment['id']);
        }

        return [
            'lesson' => $this->transform($lesson, [
                'status' => 'completed',
                'progress_percent' => 100,
                'completed_at' => $now,
            ]),
            'course_complete' => $courseComplete,
            'completed_lessons' => $completedLessons,
            'total_lessons' => $totalLessons,
        ];
    }

    private function validate(array $input, bool $creating = true): array
    {
        $data = Arr::only($input, ['title', 'slug', 'content', 'sort_order', 'duration_minutes', 'is_preview']);

        if ($creating && empty($data['title'])) {
            throw new InvalidArgumentException('Lesson title is required.');
        }

        if (isset($data['title'])) {
            $data['title'] = sanitize_text_field((string) $data['title']);
        }

        if (isset($data['content'])) {
            $data['content'] = wp_kses_post((string) $data['content']);
        }

        if ($creating) {
            $data['slug'] = isset($data['slug']) ? sanitize_title((string) $data['slug']) : '';
        } elseif (array_key_exists('slug', $data)) {
            $data['slug'] = sanitize_title((string) $data['slug']);
        }

        if (isset($data['sort_order'])) {
            $data['sort_order'] = absint($data['sort_order']);
        }

        if (isset($data['duration_minutes'])) {
            $data['duration_minutes'] = absint($data['duration_minutes']);
        }

        if (isset($data['is_preview'])) {
            $data['is_preview'] = (bool) $data['is_preview'] ? 1 : 0;
        }

        return $data;
    }

    private function transform(array $lesson, ?array $progress = null): array
    {
        return [
            'id' => (int) ($lesson['id'] ?? 0),
            'course_id' => (int) ($lesson['course_id'] ?? 0),
            'title' => $lesson['title'] ?? '',
            'slug' => $lesson['slug'] ?? '',
            'content' => $lesson['content'] ?? '',
            'sort_order' => (int) ($lesson['sort_order'] ?? 0),
            'duration_minutes' => ! empty($lesson['duration_minutes']) ? (int) $lesson['duration_minutes'] : null,
            'is_preview' => ! empty($lesson['is_preview']),
            'progress' => [
                'status' => $progress['status'] ?? 'not_started',
                'progress_percent' => isset($progress['progress_percent']) ? (float) $progress['progress_percent'] : 0,
                'completed_at' => $progress['completed_at'] ?? null,
            ],
        ];
    }
}
