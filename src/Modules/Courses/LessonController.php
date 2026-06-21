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

final class LessonController implements ControllerInterface
{
    public function __construct(private readonly LessonService $lessons)
    {
    }

    public function registerRoutes(): void
    {
        register_rest_route(RestRegistrar::NAMESPACE, '/courses/(?P<course_id>\d+)/lessons', [
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

        register_rest_route(RestRegistrar::NAMESPACE, '/lessons/(?P<id>\d+)', [
            'methods' => 'PATCH',
            'callback' => [$this, 'update'],
            'permission_callback' => Permissions::capability(Capabilities::MANAGE_COURSES),
        ]);

        register_rest_route(RestRegistrar::NAMESPACE, '/lessons/(?P<id>\d+)/complete', [
            'methods' => 'POST',
            'callback' => [$this, 'complete'],
            'permission_callback' => Permissions::authenticated(),
        ]);
    }

    public function index(WP_REST_Request $request): WP_REST_Response
    {
        return rest_ensure_response($this->lessons->listForCourse((int) $request['course_id'], get_current_user_id()));
    }

    public function store(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        try {
            return new WP_REST_Response(
                $this->lessons->create((int) $request['course_id'], $request->get_json_params() ?: []),
                201
            );
        } catch (InvalidArgumentException $exception) {
            return new WP_Error('zadora_invalid_lesson', $exception->getMessage(), ['status' => 422]);
        }
    }

    public function update(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        try {
            $lesson = $this->lessons->update((int) $request['id'], $request->get_json_params() ?: []);

            return $lesson ? rest_ensure_response($lesson) : new WP_Error('zadora_lesson_not_found', 'Lesson not found.', ['status' => 404]);
        } catch (InvalidArgumentException $exception) {
            return new WP_Error('zadora_invalid_lesson', $exception->getMessage(), ['status' => 422]);
        }
    }

    public function complete(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        try {
            $result = $this->lessons->complete((int) $request['id'], get_current_user_id());

            return $result ? rest_ensure_response($result) : new WP_Error('zadora_lesson_not_found', 'Lesson not found.', ['status' => 404]);
        } catch (InvalidArgumentException $exception) {
            return new WP_Error('zadora_lesson_progress_denied', $exception->getMessage(), ['status' => 403]);
        }
    }
}
