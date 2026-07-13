<?php

declare(strict_types=1);

namespace Zadora\Lms\Modules\Assessments;

use InvalidArgumentException;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use Zadora\Lms\Http\ControllerInterface;
use Zadora\Lms\Http\Permissions;
use Zadora\Lms\Http\RestRegistrar;
use Zadora\Lms\Security\Capabilities;

final class AssessmentController implements ControllerInterface
{
    public function __construct(private readonly AssessmentService $assessments)
    {
    }

    public function registerRoutes(): void
    {
        register_rest_route(RestRegistrar::NAMESPACE, '/courses/(?P<course_id>\d+)/assessments', [
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

        register_rest_route(RestRegistrar::NAMESPACE, '/assessments/(?P<id>\d+)', [
            'methods' => 'PATCH',
            'callback' => [$this, 'update'],
            'permission_callback' => Permissions::capability(Capabilities::MANAGE_COURSES),
        ]);

        register_rest_route(RestRegistrar::NAMESPACE, '/assessments/(?P<id>\d+)/submissions', [
            'methods' => 'POST',
            'callback' => [$this, 'submit'],
            'permission_callback' => Permissions::authenticated(),
        ]);

        register_rest_route(RestRegistrar::NAMESPACE, '/assessment-submissions', [
            'methods' => 'GET',
            'callback' => [$this, 'submissions'],
            'permission_callback' => Permissions::capability(Capabilities::GRADE_ASSESSMENTS),
        ]);

        register_rest_route(RestRegistrar::NAMESPACE, '/assessment-submissions/(?P<id>\d+)/grade', [
            'methods' => 'PATCH',
            'callback' => [$this, 'grade'],
            'permission_callback' => Permissions::capability(Capabilities::GRADE_ASSESSMENTS),
        ]);
    }

    public function index(WP_REST_Request $request): WP_REST_Response
    {
        return rest_ensure_response($this->assessments->listForCourse((int) $request['course_id']));
    }

    public function store(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        try {
            return new WP_REST_Response(
                $this->assessments->create((int) $request['course_id'], $request->get_json_params() ?: []),
                201
            );
        } catch (InvalidArgumentException $exception) {
            return new WP_Error('zadora_invalid_assessment', $exception->getMessage(), ['status' => 422]);
        }
    }

    public function update(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        try {
            $assessment = $this->assessments->update((int) $request['id'], $request->get_json_params() ?: []);

            return $assessment ? rest_ensure_response($assessment) : new WP_Error('zadora_assessment_not_found', 'Assessment not found.', ['status' => 404]);
        } catch (InvalidArgumentException $exception) {
            return new WP_Error('zadora_invalid_assessment', $exception->getMessage(), ['status' => 422]);
        }
    }

    public function submit(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        try {
            return new WP_REST_Response(
                $this->assessments->submit((int) $request['id'], get_current_user_id(), $request->get_json_params() ?: []),
                201
            );
        } catch (InvalidArgumentException $exception) {
            return new WP_Error('zadora_invalid_submission', $exception->getMessage(), ['status' => 422]);
        }
    }

    public function submissions(WP_REST_Request $request): WP_REST_Response
    {
        return rest_ensure_response($this->assessments->listSubmissions([
            'assessment_id' => $request->get_param('assessment_id'),
            'user_id' => $request->get_param('user_id'),
            'status' => $request->get_param('status'),
        ]));
    }

    public function grade(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        try {
            $submission = $this->assessments->grade((int) $request['id'], get_current_user_id(), $request->get_json_params() ?: []);

            return $submission ? rest_ensure_response($submission) : new WP_Error('zadora_submission_not_found', 'Submission not found.', ['status' => 404]);
        } catch (InvalidArgumentException $exception) {
            return new WP_Error('zadora_invalid_grade', $exception->getMessage(), ['status' => 422]);
        }
    }
}
