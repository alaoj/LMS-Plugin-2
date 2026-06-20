<?php

declare(strict_types=1);

namespace Zadora\Lms\Modules\Enrollments;

use InvalidArgumentException;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use Zadora\Lms\Http\ControllerInterface;
use Zadora\Lms\Http\Permissions;
use Zadora\Lms\Http\RestRegistrar;
use Zadora\Lms\Security\Capabilities;

final class EnrollmentController implements ControllerInterface
{
    public function __construct(private readonly EnrollmentService $enrollments)
    {
    }

    public function registerRoutes(): void
    {
        register_rest_route(RestRegistrar::NAMESPACE, '/enrollments', [
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

        register_rest_route(RestRegistrar::NAMESPACE, '/enrollments/(?P<id>\d+)/complete', [
            'methods' => 'POST',
            'callback' => [$this, 'complete'],
            'permission_callback' => Permissions::authenticated(),
        ]);
    }

    public function index(WP_REST_Request $request): WP_REST_Response
    {
        return rest_ensure_response($this->enrollments->listForUser(get_current_user_id()));
    }

    public function store(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        try {
            return new WP_REST_Response($this->enrollments->enroll($request->get_json_params() ?: []), 201);
        } catch (InvalidArgumentException $exception) {
            return new WP_Error('zadora_invalid_enrollment', $exception->getMessage(), ['status' => 422]);
        }
    }

    public function complete(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $enrollment = $this->enrollments->complete((int) $request['id']);

        return $enrollment ? rest_ensure_response($enrollment) : new WP_Error('zadora_enrollment_not_found', 'Enrollment not found.', ['status' => 404]);
    }
}
