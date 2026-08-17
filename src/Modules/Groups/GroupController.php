<?php

declare(strict_types=1);

namespace Zadora\Lms\Modules\Groups;

use InvalidArgumentException;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use Zadora\Lms\Http\ControllerInterface;
use Zadora\Lms\Http\Permissions;
use Zadora\Lms\Http\RestRegistrar;
use Zadora\Lms\Security\Capabilities;

final class GroupController implements ControllerInterface
{
    public function __construct(private readonly GroupService $groups)
    {
    }

    public function registerRoutes(): void
    {
        register_rest_route(RestRegistrar::NAMESPACE, '/groups', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'index'],
                'permission_callback' => Permissions::authenticated(),
            ],
            [
                'methods' => 'POST',
                'callback' => [$this, 'store'],
                'permission_callback' => Permissions::capability(Capabilities::MANAGE_LEARNERS),
            ],
        ]);

        register_rest_route(RestRegistrar::NAMESPACE, '/groups/(?P<id>\d+)', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'show'],
                'permission_callback' => Permissions::authenticated(),
            ],
            [
                'methods' => 'PATCH',
                'callback' => [$this, 'update'],
                'permission_callback' => Permissions::capability(Capabilities::MANAGE_LEARNERS),
            ],
        ]);

        register_rest_route(RestRegistrar::NAMESPACE, '/groups/(?P<id>\d+)/members', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'members'],
                'permission_callback' => Permissions::authenticated(),
            ],
            [
                'methods' => 'POST',
                'callback' => [$this, 'addMember'],
                'permission_callback' => Permissions::capability(Capabilities::MANAGE_LEARNERS),
            ],
        ]);

        register_rest_route(RestRegistrar::NAMESPACE, '/groups/(?P<id>\d+)/courses', [
            'methods' => 'POST',
            'callback' => [$this, 'assignCourse'],
            'permission_callback' => Permissions::capability(Capabilities::MANAGE_LEARNERS),
        ]);
    }

    public function index(WP_REST_Request $request): WP_REST_Response
    {
        return rest_ensure_response($this->groups->list([
            'company_id' => $request->get_param('company_id'),
            'user_id' => $request->get_param('mine') ? get_current_user_id() : null,
        ]));
    }

    public function show(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $group = $this->groups->find((int) $request['id']);

        return $group ? rest_ensure_response($group) : new WP_Error('zadora_group_not_found', 'Group not found.', ['status' => 404]);
    }

    public function store(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        try {
            return new WP_REST_Response($this->groups->create($request->get_json_params() ?: [], get_current_user_id()), 201);
        } catch (InvalidArgumentException $exception) {
            return new WP_Error('zadora_invalid_group', $exception->getMessage(), ['status' => 422]);
        }
    }

    public function update(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        try {
            $group = $this->groups->update((int) $request['id'], $request->get_json_params() ?: []);

            return $group ? rest_ensure_response($group) : new WP_Error('zadora_group_not_found', 'Group not found.', ['status' => 404]);
        } catch (InvalidArgumentException $exception) {
            return new WP_Error('zadora_invalid_group', $exception->getMessage(), ['status' => 422]);
        }
    }

    public function members(WP_REST_Request $request): WP_REST_Response
    {
        return rest_ensure_response($this->groups->members((int) $request['id']));
    }

    public function addMember(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        try {
            return new WP_REST_Response($this->groups->addMember((int) $request['id'], $request->get_json_params() ?: []), 201);
        } catch (InvalidArgumentException $exception) {
            return new WP_Error('zadora_invalid_group_member', $exception->getMessage(), ['status' => 422]);
        }
    }

    public function assignCourse(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        try {
            return new WP_REST_Response($this->groups->assignCourse((int) $request['id'], $request->get_json_params() ?: [], get_current_user_id()), 201);
        } catch (InvalidArgumentException $exception) {
            return new WP_Error('zadora_invalid_group_course', $exception->getMessage(), ['status' => 422]);
        }
    }
}
