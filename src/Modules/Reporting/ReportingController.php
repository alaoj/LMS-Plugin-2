<?php

declare(strict_types=1);

namespace Zadora\Lms\Modules\Reporting;

use WP_REST_Request;
use WP_REST_Response;
use Zadora\Lms\Http\ControllerInterface;
use Zadora\Lms\Http\Permissions;
use Zadora\Lms\Http\RestRegistrar;
use Zadora\Lms\Security\Capabilities;

final class ReportingController implements ControllerInterface
{
    public function __construct(private readonly ReportingRepository $reports)
    {
    }

    public function registerRoutes(): void
    {
        register_rest_route(RestRegistrar::NAMESPACE, '/reports/overview', [
            'methods' => 'GET',
            'callback' => [$this, 'overview'],
            'permission_callback' => Permissions::capability(Capabilities::VIEW_REPORTS),
        ]);

        register_rest_route(RestRegistrar::NAMESPACE, '/reports/corporate/(?P<company_id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'corporate'],
            'permission_callback' => Permissions::capability(Capabilities::VIEW_REPORTS),
        ]);

        register_rest_route(RestRegistrar::NAMESPACE, '/reports/learner', [
            'methods' => 'GET',
            'callback' => [$this, 'learner'],
            'permission_callback' => Permissions::authenticated(),
        ]);

        register_rest_route(RestRegistrar::NAMESPACE, '/reports/assessments', [
            'methods' => 'GET',
            'callback' => [$this, 'assessments'],
            'permission_callback' => Permissions::capability(Capabilities::VIEW_REPORTS),
        ]);

        register_rest_route(RestRegistrar::NAMESPACE, '/reports/ai', [
            'methods' => 'GET',
            'callback' => [$this, 'ai'],
            'permission_callback' => Permissions::capability(Capabilities::VIEW_REPORTS),
        ]);
    }

    public function overview(WP_REST_Request $request): WP_REST_Response
    {
        return rest_ensure_response($this->reports->overview());
    }

    public function corporate(WP_REST_Request $request): WP_REST_Response
    {
        return rest_ensure_response($this->reports->corporate((int) $request['company_id']));
    }

    public function learner(WP_REST_Request $request): WP_REST_Response
    {
        return rest_ensure_response($this->reports->learner(get_current_user_id()));
    }

    public function assessments(WP_REST_Request $request): WP_REST_Response
    {
        return rest_ensure_response($this->reports->assessments());
    }

    public function ai(WP_REST_Request $request): WP_REST_Response
    {
        return rest_ensure_response($this->reports->ai());
    }
}
