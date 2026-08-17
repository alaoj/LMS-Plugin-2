<?php

declare(strict_types=1);

namespace Zadora\Lms\Modules\Groups;

use InvalidArgumentException;
use Zadora\Lms\Support\Arr;
use Zadora\Lms\Support\Clock;

final class GroupService
{
    public const ROLES = ['leader', 'member'];
    public const STATUSES = ['active', 'archived'];

    public function __construct(
        private readonly GroupRepository $groups,
        private readonly Clock $clock
    ) {
    }

    public function list(array $filters = []): array
    {
        return array_map([$this, 'transform'], $this->groups->list($filters));
    }

    public function find(int $id): ?array
    {
        $group = $this->groups->find($id);

        return $group ? $this->transform($group) : null;
    }

    public function create(array $input, int $userId): array
    {
        $data = $this->validate($input);
        $now = $this->clock->now();

        $data['created_by'] = $userId;
        $data['created_at'] = $now;
        $data['updated_at'] = $now;

        $id = $this->groups->create($data);
        $this->addMember($id, ['user_id' => $userId, 'role' => 'leader']);

        return $this->find($id) ?: [];
    }

    public function update(int $id, array $input): ?array
    {
        if (! $this->groups->find($id)) {
            return null;
        }

        $data = $this->validate($input, false);
        $data['updated_at'] = $this->clock->now();

        $this->groups->update($id, $data);

        return $this->find($id);
    }

    public function addMember(int $groupId, array $input): array
    {
        if (! $this->groups->find($groupId)) {
            throw new InvalidArgumentException('Group not found.');
        }

        $userId = absint($input['user_id'] ?? 0);
        $role = sanitize_key((string) ($input['role'] ?? 'member'));

        if (! $userId) {
            throw new InvalidArgumentException('User is required.');
        }

        if (! in_array($role, self::ROLES, true)) {
            throw new InvalidArgumentException('Invalid group role.');
        }

        $now = $this->clock->now();
        $id = $this->groups->upsertMember([
            'group_id' => $groupId,
            'user_id' => $userId,
            'role' => $role,
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        do_action('zadora_lms_group_member_added', $groupId, $userId, $role);

        return [
            'id' => $id,
            'group_id' => $groupId,
            'user_id' => $userId,
            'role' => $role,
        ];
    }

    public function members(int $groupId): array
    {
        return array_map(static fn (array $member): array => [
            'id' => (int) $member['id'],
            'group_id' => (int) $member['group_id'],
            'user_id' => (int) $member['user_id'],
            'role' => $member['role'],
            'status' => $member['status'],
            'created_at' => $member['created_at'],
        ], $this->groups->listMembers($groupId));
    }

    public function assignCourse(int $groupId, array $input, int $assignedBy): array
    {
        if (! $this->groups->find($groupId)) {
            throw new InvalidArgumentException('Group not found.');
        }

        $courseId = absint($input['course_id'] ?? 0);

        if (! $courseId) {
            throw new InvalidArgumentException('Course is required.');
        }

        $id = $this->groups->assignCourse([
            'group_id' => $groupId,
            'course_id' => $courseId,
            'assigned_by' => $assignedBy,
            'created_at' => $this->clock->now(),
        ]);

        do_action('zadora_lms_group_course_assigned', $groupId, $courseId, $assignedBy);

        return [
            'id' => $id,
            'group_id' => $groupId,
            'course_id' => $courseId,
        ];
    }

    private function validate(array $input, bool $creating = true): array
    {
        $data = Arr::only($input, ['company_id', 'name', 'description', 'status']);

        if ($creating && empty($data['name'])) {
            throw new InvalidArgumentException('Group name is required.');
        }

        if (isset($data['name'])) {
            $data['name'] = sanitize_text_field((string) $data['name']);
        }

        if (isset($data['description'])) {
            $data['description'] = sanitize_textarea_field((string) $data['description']);
        }

        if (isset($data['company_id'])) {
            $data['company_id'] = absint($data['company_id']) ?: null;
        }

        if ($creating) {
            $data['status'] = $data['status'] ?? 'active';
        }

        if (isset($data['status'])) {
            $data['status'] = sanitize_key((string) $data['status']);

            if (! in_array($data['status'], self::STATUSES, true)) {
                throw new InvalidArgumentException('Invalid group status.');
            }
        }

        return $data;
    }

    private function transform(array $group): array
    {
        $metrics = $this->groups->metrics((int) $group['id']);

        return [
            'id' => (int) $group['id'],
            'company_id' => ! empty($group['company_id']) ? (int) $group['company_id'] : null,
            'name' => $group['name'],
            'description' => $group['description'],
            'status' => $group['status'],
            'created_by' => (int) $group['created_by'],
            'members' => $metrics['members'],
            'courses' => $metrics['courses'],
            'created_at' => $group['created_at'],
            'updated_at' => $group['updated_at'],
        ];
    }
}
