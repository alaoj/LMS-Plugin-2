<?php

declare(strict_types=1);

namespace Zadora\Lms\Modules\Courses;

use InvalidArgumentException;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use Zadora\Lms\Http\ControllerInterface;
use Zadora\Lms\Http\Permissions;
use Zadora\Lms\Http\RestRegistrar;
use Zadora\Lms\Security\Capabilities;

final class CourseController implements ControllerInterface
{
    public function __construct(private readonly CourseService $courses)
    {
    }

    public function registerRoutes(): void
    {
        register_rest_route(RestRegistrar::NAMESPACE, '/courses', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'index'],
                'permission_callback' => Permissions::authenticated(),
            ],
            [
                'methods' => 'POST',
                'callback' => [$this, 'store'],
                'permission_callback' => Permissions::capability(Capabilities::MANAGE_COURSES),
            ],
        ]);

        register_rest_route(RestRegistrar::NAMESPACE, '/courses/(?P<id>\d+)', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'show'],
                'permission_callback' => Permissions::authenticated(),
            ],
            [
                'methods' => 'PATCH',
                'callback' => [$this, 'update'],
                'permission_callback' => Permissions::capability(Capabilities::MANAGE_COURSES),
            ],
        ]);

        register_rest_route(RestRegistrar::NAMESPACE, '/courses/(?P<id>\d+)/waitlist', [
            'methods' => 'POST',
            'callback' => [$this, 'joinWaitlist'],
            'permission_callback' => Permissions::authenticated(),
        ]);
    }

    public function index(WP_REST_Request $request): WP_REST_Response
    {
        return rest_ensure_response($this->courses->list([
            'status' => $request->get_param('status'),
            'visibility' => $request->get_param('visibility'),
        ]));
    }

    public function show(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $course = $this->courses->find((int) $request['id']);

        return $course ? rest_ensure_response($course) : new WP_Error('zadora_course_not_found', 'Course not found.', ['status' => 404]);
    }

    public function store(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        try {
            $course = $this->courses->create($request->get_json_params() ?: [], get_current_user_id());

            return new WP_REST_Response($course, 201);
        } catch (InvalidArgumentException $exception) {
            return new WP_Error('zadora_invalid_course', $exception->getMessage(), ['status' => 422]);
        }
    }

    public function update(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        try {
            $course = $this->courses->update((int) $request['id'], $request->get_json_params() ?: []);

            return $course ? rest_ensure_response($course) : new WP_Error('zadora_course_not_found', 'Course not found.', ['status' => 404]);
        } catch (InvalidArgumentException $exception) {
            return new WP_Error('zadora_invalid_course', $exception->getMessage(), ['status' => 422]);
        }
    }

    public function joinWaitlist(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        try {
            return new WP_REST_Response($this->courses->joinWaitlist((int) $request['id'], get_current_user_id()), 201);
        } catch (InvalidArgumentException $exception) {
            return new WP_Error('zadora_waitlist_denied', $exception->getMessage(), ['status' => 422]);
        }
    }
}
