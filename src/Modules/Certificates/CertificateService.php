<?php

declare(strict_types=1);

namespace Zadora\Lms\Modules\Certificates;

use InvalidArgumentException;
use Zadora\Lms\Support\Clock;

final class CertificateService
{
    public function __construct(
        private readonly CertificateRepository $certificates,
        private readonly Clock $clock
    ) {
    }

    public function wallet(int $userId): array
    {
        return array_map([$this, 'transform'], $this->certificates->listForUser($userId));
    }

    public function templates(int $ownerId = 0): array
    {
        return array_map([$this, 'transformTemplate'], $this->certificates->listTemplates($ownerId));
    }

    public function createTemplate(array $input, int $ownerId): array
    {
        $name = sanitize_text_field((string) ($input['name'] ?? ''));

        if ($name === '') {
            throw new InvalidArgumentException('Template name is required.');
        }

        $layout = $input['layout'] ?? [];

        if (! is_array($layout)) {
            throw new InvalidArgumentException('Template layout must be an object.');
        }

        $now = $this->clock->now();
        $id = $this->certificates->createTemplate([
            'owner_id' => $ownerId,
            'company_id' => ! empty($input['company_id']) ? absint($input['company_id']) : null,
            'name' => $name,
            'layout_json' => wp_json_encode($layout, JSON_THROW_ON_ERROR),
            'background_asset_id' => ! empty($input['background_asset_id']) ? absint($input['background_asset_id']) : null,
            'is_default' => ! empty($input['is_default']) ? 1 : 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $this->transformTemplate($this->certificates->findTemplate($id) ?: []);
    }

    public function updateTemplate(int $id, array $input): ?array
    {
        if (! $this->certificates->findTemplate($id)) {
            return null;
        }

        $data = [];

        if (array_key_exists('name', $input)) {
            $data['name'] = sanitize_text_field((string) $input['name']);
        }

        if (array_key_exists('layout', $input)) {
            if (! is_array($input['layout'])) {
                throw new InvalidArgumentException('Template layout must be an object.');
            }

            $data['layout_json'] = wp_json_encode($input['layout'], JSON_THROW_ON_ERROR);
        }

        if (array_key_exists('background_asset_id', $input)) {
            $data['background_asset_id'] = ! empty($input['background_asset_id']) ? absint($input['background_asset_id']) : null;
        }

        if (array_key_exists('is_default', $input)) {
            $data['is_default'] = ! empty($input['is_default']) ? 1 : 0;
        }

        $data['updated_at'] = $this->clock->now();

        $this->certificates->updateTemplate($id, $data);

        return $this->transformTemplate($this->certificates->findTemplate($id) ?: []);
    }

    public function issue(array $input): array
    {
        $userId = absint($input['user_id'] ?? 0);
        $courseId = absint($input['course_id'] ?? 0);
        $templateId = absint($input['template_id'] ?? 0);
        $displayName = sanitize_text_field((string) ($input['display_name'] ?? ''));

        if (! $userId || ! $courseId || ! $templateId || $displayName === '') {
            throw new InvalidArgumentException('Learner, course, template, and certificate name are required.');
        }

        $now = $this->clock->now();
        $number = $this->makeCertificateNumber($userId, $courseId);
        $hash = wp_hash($number . '|' . $userId . '|' . $now);

        $id = $this->certificates->create([
            'certificate_number' => $number,
            'verification_hash' => $hash,
            'user_id' => $userId,
            'course_id' => $courseId,
            'template_id' => $templateId,
            'display_name' => $displayName,
            'snapshot_json' => wp_json_encode($input['snapshot'] ?? [], JSON_THROW_ON_ERROR),
            'pdf_url' => null,
            'issued_at' => $now,
            'expires_at' => ! empty($input['expires_at']) ? sanitize_text_field((string) $input['expires_at']) : null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $issued = $this->certificates->listForUser($userId);

        foreach ($issued as $certificate) {
            if ((int) $certificate['id'] === $id) {
                return $this->transform($certificate);
            }
        }

        return [];
    }

    public function issueForCompletedCourse(int $courseId, int $userId, int $enrollmentId): ?array
    {
        $course = $this->certificates->findCourse($courseId);

        if (! $course || empty($course['certificate_template_id'])) {
            return null;
        }

        $existing = $this->certificates->findForUserAndCourse($userId, $courseId);

        if ($existing) {
            return $this->transform($existing);
        }

        $user = get_userdata($userId);
        $displayName = $user ? trim((string) $user->display_name) : '';

        if ($displayName === '') {
            $displayName = 'Learner';
        }

        $certificate = $this->issue([
            'user_id' => $userId,
            'course_id' => $courseId,
            'template_id' => (int) $course['certificate_template_id'],
            'display_name' => $displayName,
            'snapshot' => [
                'course_name' => $course['title'],
                'enrollment_id' => $enrollmentId,
                'issued_reason' => 'course_completion',
            ],
        ]);

        do_action('zadora_lms_certificate_issued', $certificate, $course, $userId);

        return $certificate;
    }

    public function verify(string $hash): ?array
    {
        $certificate = $this->certificates->findByHash(sanitize_text_field($hash));

        if (! $certificate || ! empty($certificate['revoked_at'])) {
            return null;
        }

        return $this->transform($certificate);
    }

    private function makeCertificateNumber(int $userId, int $courseId): string
    {
        return sprintf('ZD-%d-%d-%s', $courseId, $userId, strtoupper(wp_generate_password(8, false, false)));
    }

    private function transformTemplate(array $template): array
    {
        return [
            'id' => (int) ($template['id'] ?? 0),
            'owner_id' => (int) ($template['owner_id'] ?? 0),
            'company_id' => ! empty($template['company_id']) ? (int) $template['company_id'] : null,
            'name' => $template['name'] ?? '',
            'layout' => ! empty($template['layout_json']) ? json_decode((string) $template['layout_json'], true) : [],
            'background_asset_id' => ! empty($template['background_asset_id']) ? (int) $template['background_asset_id'] : null,
            'is_default' => ! empty($template['is_default']),
            'created_at' => $template['created_at'] ?? null,
            'updated_at' => $template['updated_at'] ?? null,
        ];
    }

    private function transform(array $certificate): array
    {
        return [
            'id' => (int) $certificate['id'],
            'certificate_number' => $certificate['certificate_number'],
            'verification_hash' => $certificate['verification_hash'],
            'user_id' => (int) $certificate['user_id'],
            'course_id' => (int) $certificate['course_id'],
            'template_id' => (int) $certificate['template_id'],
            'display_name' => $certificate['display_name'],
            'pdf_url' => $certificate['pdf_url'],
            'issued_at' => $certificate['issued_at'],
            'expires_at' => $certificate['expires_at'],
        ];
    }
}
