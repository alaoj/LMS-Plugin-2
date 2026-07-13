<?php

declare(strict_types=1);

namespace Zadora\Lms\Modules\Assessments;

use InvalidArgumentException;
use Zadora\Lms\Support\Arr;
use Zadora\Lms\Support\Clock;

final class AssessmentService
{
    public const TYPES = ['quiz', 'essay', 'assignment'];
    public const SUBMISSION_STATUSES = ['submitted', 'graded', 'returned'];

    public function __construct(
        private readonly AssessmentRepository $assessments,
        private readonly Clock $clock
    ) {
    }

    public function listForCourse(int $courseId): array
    {
        return array_map([$this, 'transformAssessment'], $this->assessments->listForCourse($courseId));
    }

    public function create(int $courseId, array $input): array
    {
        $data = $this->validateAssessment($input);
        $now = $this->clock->now();

        $data['course_id'] = $courseId;
        $data['created_at'] = $now;
        $data['updated_at'] = $now;

        $id = $this->assessments->create($data);

        return $this->transformAssessment($this->assessments->find($id) ?: []);
    }

    public function update(int $id, array $input): ?array
    {
        if (! $this->assessments->find($id)) {
            return null;
        }

        $data = $this->validateAssessment($input, false);
        $data['updated_at'] = $this->clock->now();

        $this->assessments->update($id, $data);

        return $this->transformAssessment($this->assessments->find($id) ?: []);
    }

    public function submit(int $assessmentId, int $userId, array $input): array
    {
        $assessment = $this->assessments->find($assessmentId);

        if (! $assessment) {
            throw new InvalidArgumentException('Assessment not found.');
        }

        if (! $this->assessments->userIsEnrolled($userId, (int) $assessment['course_id'])) {
            throw new InvalidArgumentException('Learner is not enrolled in this course.');
        }

        $answers = $input['answers'] ?? [];

        if (! is_array($answers)) {
            throw new InvalidArgumentException('Answers must be an object.');
        }

        $now = $this->clock->now();
        $score = $this->autoScoreIfPossible($assessment, $answers);
        $status = $score === null ? 'submitted' : 'graded';

        $id = $this->assessments->createSubmission([
            'assessment_id' => $assessmentId,
            'user_id' => $userId,
            'status' => $status,
            'score' => $score,
            'answers_json' => wp_json_encode($answers, JSON_THROW_ON_ERROR),
            'feedback' => $score === null ? null : 'Automatically scored.',
            'graded_by' => $score === null ? null : 0,
            'graded_at' => $score === null ? null : $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $submission = $this->transformSubmission($this->assessments->findSubmission($id) ?: []);

        do_action('zadora_lms_assessment_submitted', $submission, $assessment);

        if ($status === 'graded') {
            do_action('zadora_lms_assessment_graded', $submission, $assessment);
        }

        return $submission;
    }

    public function listSubmissions(array $filters = []): array
    {
        return array_map([$this, 'transformSubmission'], $this->assessments->listSubmissions($filters));
    }

    public function grade(int $submissionId, int $graderId, array $input): ?array
    {
        $submission = $this->assessments->findSubmission($submissionId);

        if (! $submission) {
            return null;
        }

        $score = (float) ($input['score'] ?? -1);

        if ($score < 0 || $score > 100) {
            throw new InvalidArgumentException('Score must be between 0 and 100.');
        }

        $now = $this->clock->now();
        $this->assessments->gradeSubmission($submissionId, [
            'status' => 'graded',
            'score' => $score,
            'feedback' => isset($input['feedback']) ? sanitize_textarea_field((string) $input['feedback']) : null,
            'graded_by' => $graderId,
            'graded_at' => $now,
            'updated_at' => $now,
        ]);

        $graded = $this->transformSubmission($this->assessments->findSubmission($submissionId) ?: []);
        $assessment = $this->assessments->find((int) $submission['assessment_id']) ?: [];

        do_action('zadora_lms_assessment_graded', $graded, $assessment);

        return $graded;
    }

    private function validateAssessment(array $input, bool $creating = true): array
    {
        $data = Arr::only($input, ['title', 'type', 'settings', 'passing_score']);

        if ($creating && empty($data['title'])) {
            throw new InvalidArgumentException('Assessment title is required.');
        }

        if (isset($data['title'])) {
            $data['title'] = sanitize_text_field((string) $data['title']);
        }

        if ($creating) {
            $data['type'] = $data['type'] ?? 'quiz';
            $data['settings'] = $data['settings'] ?? [];
            $data['passing_score'] = $data['passing_score'] ?? 70;
        }

        if (isset($data['type'])) {
            $data['type'] = sanitize_key((string) $data['type']);

            if (! in_array($data['type'], self::TYPES, true)) {
                throw new InvalidArgumentException('Invalid assessment type.');
            }
        }

        if (isset($data['settings'])) {
            if (! is_array($data['settings'])) {
                throw new InvalidArgumentException('Assessment settings must be an object.');
            }

            $data['settings_json'] = wp_json_encode($data['settings'], JSON_THROW_ON_ERROR);
            unset($data['settings']);
        }

        if (isset($data['passing_score'])) {
            $data['passing_score'] = max(0, min(100, (float) $data['passing_score']));
        }

        return $data;
    }

    private function autoScoreIfPossible(array $assessment, array $answers): ?float
    {
        if ($assessment['type'] !== 'quiz') {
            return null;
        }

        $settings = json_decode((string) $assessment['settings_json'], true) ?: [];
        $questions = $settings['questions'] ?? [];

        if (! is_array($questions) || count($questions) === 0) {
            return null;
        }

        $score = 0;
        $gradable = 0;

        foreach ($questions as $question) {
            if (! isset($question['id'], $question['answer'])) {
                continue;
            }

            $gradable++;

            if (($answers[(string) $question['id']] ?? null) === $question['answer']) {
                $score++;
            }
        }

        return $gradable > 0 ? round(($score / $gradable) * 100, 2) : null;
    }

    private function transformAssessment(array $assessment): array
    {
        return [
            'id' => (int) ($assessment['id'] ?? 0),
            'course_id' => (int) ($assessment['course_id'] ?? 0),
            'title' => $assessment['title'] ?? '',
            'type' => $assessment['type'] ?? '',
            'settings' => ! empty($assessment['settings_json']) ? json_decode((string) $assessment['settings_json'], true) : [],
            'passing_score' => isset($assessment['passing_score']) ? (float) $assessment['passing_score'] : 0,
            'created_at' => $assessment['created_at'] ?? null,
            'updated_at' => $assessment['updated_at'] ?? null,
        ];
    }

    private function transformSubmission(array $submission): array
    {
        return [
            'id' => (int) ($submission['id'] ?? 0),
            'assessment_id' => (int) ($submission['assessment_id'] ?? 0),
            'user_id' => (int) ($submission['user_id'] ?? 0),
            'status' => $submission['status'] ?? '',
            'score' => isset($submission['score']) ? (float) $submission['score'] : null,
            'answers' => ! empty($submission['answers_json']) ? json_decode((string) $submission['answers_json'], true) : [],
            'feedback' => $submission['feedback'] ?? null,
            'graded_by' => ! empty($submission['graded_by']) ? (int) $submission['graded_by'] : null,
            'graded_at' => $submission['graded_at'] ?? null,
            'created_at' => $submission['created_at'] ?? null,
            'updated_at' => $submission['updated_at'] ?? null,
        ];
    }
}
