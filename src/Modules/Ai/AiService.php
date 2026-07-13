<?php

declare(strict_types=1);

namespace Zadora\Lms\Modules\Ai;

use InvalidArgumentException;
use Zadora\Lms\Modules\Settings\SettingsService;
use Zadora\Lms\Support\Clock;

final class AiService
{
    public const TASKS = ['quiz', 'objectives', 'grading_suggestion'];

    public function __construct(
        private readonly AiRepository $requests,
        private readonly SettingsService $settings,
        private readonly Clock $clock
    ) {
    }

    public function generateQuiz(array $input, int $userId): array
    {
        $content = trim(wp_strip_all_tags((string) ($input['content'] ?? '')));

        if ($content === '') {
            throw new InvalidArgumentException('Course content is required.');
        }

        $count = max(1, min(20, absint($input['count'] ?? 5)));
        $sentences = $this->sentences($content);
        $questions = [];

        for ($i = 0; $i < $count; $i++) {
            $source = $sentences[$i % count($sentences)] ?? $content;
            $questions[] = [
                'id' => 'q' . ($i + 1),
                'type' => 'mcq',
                'question' => 'Which statement best reflects this course idea?',
                'options' => [
                    $this->shorten($source),
                    'A process unrelated to the course topic',
                    'A general workplace preference',
                    'An optional administrative detail',
                ],
                'answer' => $this->shorten($source),
            ];
        }

        return $this->complete('quiz', $userId, $input, ['questions' => $questions]);
    }

    public function generateObjectives(array $input, int $userId): array
    {
        $title = sanitize_text_field((string) ($input['title'] ?? ''));

        if ($title === '') {
            throw new InvalidArgumentException('Course title is required.');
        }

        $objectives = [
            sprintf('Explain the core concepts of %s in practical workplace language.', $title),
            sprintf('Apply %s principles to real operational scenarios.', $title),
            sprintf('Identify common risks, errors, and compliance expectations related to %s.', $title),
            sprintf('Demonstrate understanding of %s through assessment and reflection.', $title),
        ];

        return $this->complete('objectives', $userId, $input, ['objectives' => $objectives]);
    }

    public function gradingSuggestion(array $input, int $userId): array
    {
        $submission = trim(wp_strip_all_tags((string) ($input['submission'] ?? '')));
        $rubric = trim(wp_strip_all_tags((string) ($input['rubric'] ?? '')));

        if ($submission === '') {
            throw new InvalidArgumentException('Submission text is required.');
        }

        $lengthScore = min(80, max(35, strlen($submission) / 8));
        $rubricBonus = $rubric !== '' ? 10 : 0;
        $score = round(min(95, $lengthScore + $rubricBonus), 2);

        return $this->complete('grading_suggestion', $userId, $input, [
            'suggested_score' => $score,
            'feedback' => 'Review the response against the rubric, confirm accuracy, and adjust this suggestion before publishing.',
            'requires_instructor_approval' => true,
        ]);
    }

    private function complete(string $task, int $userId, array $input, array $result): array
    {
        $this->guardUsage($userId);

        $settings = $this->settings->section('ai', true);
        $provider = sanitize_key((string) ($settings['provider'] ?? 'openai'));
        $now = $this->clock->now();

        $record = [
            'user_id' => $userId,
            'company_id' => ! empty($input['company_id']) ? absint($input['company_id']) : null,
            'provider' => $provider,
            'task' => $task,
            'status' => 'completed',
            'input_hash' => wp_hash(wp_json_encode($input) ?: ''),
            'tokens_in' => $this->estimateTokens(wp_json_encode($input)),
            'tokens_out' => $this->estimateTokens(wp_json_encode($result)),
            'cost_minor' => 0,
            'result_json' => wp_json_encode($result, JSON_THROW_ON_ERROR),
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $id = $this->requests->log($record);

        return [
            'id' => $id,
            'provider' => $provider,
            'task' => $task,
            'status' => 'completed',
            'result' => $result,
            'requires_review' => true,
        ];
    }

    private function guardUsage(int $userId): void
    {
        $settings = $this->settings->section('ai', true);
        $limit = absint($settings['monthly_usage_limit'] ?? 0);

        if ($limit > 0 && $this->requests->countForUserThisMonth($userId) >= $limit) {
            throw new InvalidArgumentException('Monthly AI usage limit reached.');
        }
    }

    /** @return string[] */
    private function sentences(string $content): array
    {
        $parts = preg_split('/(?<=[.!?])\s+/', $content) ?: [];
        $parts = array_values(array_filter(array_map('trim', $parts)));

        return $parts ?: [$content];
    }

    private function shorten(string $value): string
    {
        return substr(trim($value), 0, 140);
    }

    private function estimateTokens(string|false $value): int
    {
        if (! is_string($value)) {
            return 0;
        }

        return (int) ceil(strlen($value) / 4);
    }
}
