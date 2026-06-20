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
