<?php

declare(strict_types=1);

namespace Zadora\Lms\Modules\Companies;

use InvalidArgumentException;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use Zadora\Lms\Http\ControllerInterface;
use Zadora\Lms\Http\Permissions;
use Zadora\Lms\Http\RestRegistrar;
use Zadora\Lms\Security\Capabilities;

final class CompanyController implements ControllerInterface
{
    public function __construct(private readonly CompanyService $companies)
    {
    }

    public function registerRoutes(): void
    {
        register_rest_route(RestRegistrar::NAMESPACE, '/companies', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'index'],
                'permission_callback' => Permissions::capability(Capabilities::MANAGE_COMPANY),
            ],
            [
                'methods' => 'POST',
                'callback' => [$this, 'store'],
                'permission_callback' => Permissions::capability(Capabilities::MANAGE_COMPANY),
            ],
        ]);

        register_rest_route(RestRegistrar::NAMESPACE, '/companies/(?P<id>\d+)', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'show'],
                'permission_callback' => Permissions::capability(Capabilities::MANAGE_COMPANY),
            ],
            [
                'methods' => 'PATCH',
                'callback' => [$this, 'update'],
                'permission_callback' => Permissions::capability(Capabilities::MANAGE_COMPANY),
            ],
        ]);

        register_rest_route(RestRegistrar::NAMESPACE, '/companies/(?P<id>\d+)/departments', [
            'methods' => 'POST',
            'callback' => [$this, 'addDepartment'],
            'permission_callback' => Permissions::capability(Capabilities::MANAGE_COMPANY),
        ]);

        register_rest_route(RestRegistrar::NAMESPACE, '/companies/(?P<id>\d+)/users', [
            'methods' => 'POST',
            'callback' => [$this, 'addUser'],
            'permission_callback' => Permissions::capability(Capabilities::MANAGE_COMPANY),
        ]);
    }

    public function index(WP_REST_Request $request): WP_REST_Response
    {
        return rest_ensure_response($this->companies->list());
    }

    public function show(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $company = $this->companies->find((int) $request['id']);

        return $company ? rest_ensure_response($company) : new WP_Error('zadora_company_not_found', 'Company not found.', ['status' => 404]);
    }

    public function store(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        try {
            return new WP_REST_Response($this->companies->create($request->get_json_params() ?: []), 201);
        } catch (InvalidArgumentException $exception) {
            return new WP_Error('zadora_invalid_company', $exception->getMessage(), ['status' => 422]);
        }
    }

    public function update(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        try {
            $company = $this->companies->update((int) $request['id'], $request->get_json_params() ?: []);

            return $company ? rest_ensure_response($company) : new WP_Error('zadora_company_not_found', 'Company not found.', ['status' => 404]);
        } catch (InvalidArgumentException $exception) {
            return new WP_Error('zadora_invalid_company', $exception->getMessage(), ['status' => 422]);
        }
    }

    public function addDepartment(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        try {
            return new WP_REST_Response($this->companies->addDepartment((int) $request['id'], $request->get_json_params() ?: []), 201);
        } catch (InvalidArgumentException $exception) {
            return new WP_Error('zadora_invalid_department', $exception->getMessage(), ['status' => 422]);
        }
    }

    public function addUser(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        try {
            return new WP_REST_Response($this->companies->addUser((int) $request['id'], $request->get_json_params() ?: []), 201);
        } catch (InvalidArgumentException $exception) {
            return new WP_Error('zadora_invalid_company_user', $exception->getMessage(), ['status' => 422]);
        }
    }
}
