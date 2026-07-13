<?php

declare(strict_types=1);

namespace Zadora\Lms\Modules\Ai;

use InvalidArgumentException;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use Zadora\Lms\Http\ControllerInterface;
use Zadora\Lms\Http\Permissions;
use Zadora\Lms\Http\RestRegistrar;
use Zadora\Lms\Security\Capabilities;

final class AiController implements ControllerInterface
{
    public function __construct(private readonly AiService $ai)
    {
    }

    public function registerRoutes(): void
    {
        register_rest_route(RestRegistrar::NAMESPACE, '/ai/quiz', [
            'methods' => 'POST',
            'callback' => [$this, 'quiz'],
            'permission_callback' => Permissions::capability(Capabilities::MANAGE_COURSES),
        ]);

        register_rest_route(RestRegistrar::NAMESPACE, '/ai/objectives', [
            'methods' => 'POST',
            'callback' => [$this, 'objectives'],
            'permission_callback' => Permissions::capability(Capabilities::MANAGE_COURSES),
        ]);

        register_rest_route(RestRegistrar::NAMESPACE, '/ai/grading-suggestion', [
            'methods' => 'POST',
            'callback' => [$this, 'gradingSuggestion'],
            'permission_callback' => Permissions::capability(Capabilities::GRADE_ASSESSMENTS),
        ]);
    }

    public function quiz(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        return $this->handle(fn (): array => $this->ai->generateQuiz($request->get_json_params() ?: [], get_current_user_id()));
    }

    public function objectives(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        return $this->handle(fn (): array => $this->ai->generateObjectives($request->get_json_params() ?: [], get_current_user_id()));
    }

    public function gradingSuggestion(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        return $this->handle(fn (): array => $this->ai->gradingSuggestion($request->get_json_params() ?: [], get_current_user_id()));
    }

    private function handle(callable $callback): WP_REST_Response|WP_Error
    {
        try {
            return rest_ensure_response($callback());
        } catch (InvalidArgumentException $exception) {
            return new WP_Error('zadora_ai_request_invalid', $exception->getMessage(), ['status' => 422]);
        }
    }
}
