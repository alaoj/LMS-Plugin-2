<?php

declare(strict_types=1);

namespace Zadora\Lms\Modules\Certificates;

use Zadora\Lms\Database\TableNames;

final class CertificateRepository
{
    public function __construct(private readonly TableNames $tables)
    {
    }

    public function listForUser(int $userId): array
    {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$this->tables->certificates()} WHERE user_id = %d ORDER BY issued_at DESC", $userId),
            ARRAY_A
        ) ?: [];
    }

    public function listTemplates(int $ownerId = 0): array
    {
        global $wpdb;

        if ($ownerId > 0) {
            return $wpdb->get_results(
                $wpdb->prepare("SELECT * FROM {$this->tables->certificateTemplates()} WHERE owner_id = %d ORDER BY created_at DESC", $ownerId),
                ARRAY_A
            ) ?: [];
        }

        return $wpdb->get_results("SELECT * FROM {$this->tables->certificateTemplates()} ORDER BY created_at DESC LIMIT 100", ARRAY_A) ?: [];
    }

    public function findTemplate(int $id): ?array
    {
        global $wpdb;

        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->tables->certificateTemplates()} WHERE id = %d LIMIT 1", $id),
            ARRAY_A
        );

        return $row ?: null;
    }

    public function createTemplate(array $data): int
    {
        global $wpdb;

        $wpdb->insert($this->tables->certificateTemplates(), $data);

        return (int) $wpdb->insert_id;
    }

    public function updateTemplate(int $id, array $data): bool
    {
        global $wpdb;

        return $wpdb->update($this->tables->certificateTemplates(), $data, ['id' => $id]) !== false;
    }

    public function findByHash(string $hash): ?array
    {
        global $wpdb;

        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->tables->certificates()} WHERE verification_hash = %s LIMIT 1", $hash),
            ARRAY_A
        );

        return $row ?: null;
    }

    public function findForUserAndCourse(int $userId, int $courseId): ?array
    {
        global $wpdb;

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->tables->certificates()} WHERE user_id = %d AND course_id = %d AND revoked_at IS NULL LIMIT 1",
                $userId,
                $courseId
            ),
            ARRAY_A
        );

        return $row ?: null;
    }

    public function findCourse(int $courseId): ?array
    {
        global $wpdb;

        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->tables->courses()} WHERE id = %d LIMIT 1", $courseId),
            ARRAY_A
        );

        return $row ?: null;
    }

    public function create(array $data): int
    {
        global $wpdb;

        $wpdb->insert($this->tables->certificates(), $data);

        return (int) $wpdb->insert_id;
    }
}
