<?php

declare(strict_types=1);

namespace Zadora\Lms\Modules\Companies;

use InvalidArgumentException;
use Zadora\Lms\Support\Arr;
use Zadora\Lms\Support\Clock;

final class CompanyService
{
    public const STATUSES = ['active', 'paused', 'archived'];
    public const USER_ROLES = ['admin', 'manager', 'employee'];

    public function __construct(
        private readonly CompanyRepository $companies,
        private readonly Clock $clock
    ) {
    }

    public function list(): array
    {
        return array_map([$this, 'transform'], $this->companies->list());
    }

    public function find(int $id): ?array
    {
        $company = $this->companies->find($id);

        return $company ? $this->transform($company) : null;
    }

    public function create(array $input): array
    {
        $data = $this->validateCompany($input);
        $now = $this->clock->now();

        $data['slug'] = sanitize_title($data['slug'] ?: $data['name']);
        $data['created_at'] = $now;
        $data['updated_at'] = $now;

        $id = $this->companies->create($data);

        return $this->find($id) ?: [];
    }

    public function update(int $id, array $input): ?array
    {
        if (! $this->companies->find($id)) {
            return null;
        }

        $data = $this->validateCompany($input, false);
        $data['updated_at'] = $this->clock->now();

        if (isset($data['slug'])) {
            $data['slug'] = sanitize_title((string) $data['slug']);
        }

        $this->companies->update($id, $data);

        return $this->find($id);
    }

    public function addDepartment(int $companyId, array $input): array
    {
        if (! $this->companies->find($companyId)) {
            throw new InvalidArgumentException('Company not found.');
        }

        $name = sanitize_text_field((string) ($input['name'] ?? ''));

        if ($name === '') {
            throw new InvalidArgumentException('Department name is required.');
        }

        $now = $this->clock->now();
        $id = $this->companies->createDepartment([
            'company_id' => $companyId,
            'parent_id' => ! empty($input['parent_id']) ? absint($input['parent_id']) : null,
            'name' => $name,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return [
            'id' => $id,
            'company_id' => $companyId,
            'name' => $name,
        ];
    }

    public function addUser(int $companyId, array $input): array
    {
        if (! $this->companies->find($companyId)) {
            throw new InvalidArgumentException('Company not found.');
        }

        $userId = absint($input['user_id'] ?? 0);
        $role = sanitize_key((string) ($input['role'] ?? 'employee'));

        if (! $userId) {
            throw new InvalidArgumentException('User is required.');
        }

        if (! in_array($role, self::USER_ROLES, true)) {
            throw new InvalidArgumentException('Invalid company user role.');
        }

        $now = $this->clock->now();
        $id = $this->companies->addUser([
            'company_id' => $companyId,
            'user_id' => $userId,
            'department_id' => ! empty($input['department_id']) ? absint($input['department_id']) : null,
            'role' => $role,
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return [
            'id' => $id,
            'company_id' => $companyId,
            'user_id' => $userId,
            'role' => $role,
        ];
    }

    private function validateCompany(array $input, bool $creating = true): array
    {
        $data = Arr::only($input, ['name', 'slug', 'logo_url', 'status', 'primary_admin_id', 'seat_limit']);

        if ($creating && empty($data['name'])) {
            throw new InvalidArgumentException('Company name is required.');
        }

        if (isset($data['name'])) {
            $data['name'] = sanitize_text_field((string) $data['name']);
        }

        if (isset($data['logo_url'])) {
            $data['logo_url'] = esc_url_raw((string) $data['logo_url']);
        }

        if ($creating) {
            $data['status'] = $data['status'] ?? 'active';
            $data['seat_limit'] = $data['seat_limit'] ?? 0;
        }

        if (isset($data['status'])) {
            $data['status'] = sanitize_key((string) $data['status']);

            if (! in_array($data['status'], self::STATUSES, true)) {
                throw new InvalidArgumentException('Invalid company status.');
            }
        }

        $data['slug'] = isset($data['slug']) ? sanitize_title((string) $data['slug']) : '';

        if (isset($data['primary_admin_id'])) {
            $data['primary_admin_id'] = absint($data['primary_admin_id']) ?: null;
        }

        if (isset($data['seat_limit'])) {
            $data['seat_limit'] = max(0, absint($data['seat_limit']));
        }

        return $data;
    }

    private function transform(array $company): array
    {
        $metrics = $this->companies->companyMetrics((int) $company['id']);

        return [
            'id' => (int) $company['id'],
            'name' => $company['name'],
            'slug' => $company['slug'],
            'logo_url' => $company['logo_url'],
            'status' => $company['status'],
            'primary_admin_id' => ! empty($company['primary_admin_id']) ? (int) $company['primary_admin_id'] : null,
            'seat_limit' => (int) $company['seat_limit'],
            'active_users' => $metrics['active_users'],
            'departments' => $metrics['departments'],
            'created_at' => $company['created_at'],
            'updated_at' => $company['updated_at'],
        ];
    }
}
