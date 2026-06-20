<?php

declare(strict_types=1);

namespace Zadora\Lms\Modules\Certificates;

use InvalidArgumentException;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use Zadora\Lms\Http\ControllerInterface;
use Zadora\Lms\Http\Permissions;
use Zadora\Lms\Http\RestRegistrar;
use Zadora\Lms\Security\Capabilities;

final class CertificateController implements ControllerInterface
{
    public function __construct(private readonly CertificateService $certificates)
    {
    }

    public function registerRoutes(): void
    {
        register_rest_route(RestRegistrar::NAMESPACE, '/certificates', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'wallet'],
                'permission_callback' => Permissions::authenticated(),
            ],
            [
                'methods' => 'POST',
                'callback' => [$this, 'issue'],
                'permission_callback' => Permissions::capability(Capabilities::MANAGE_CERTIFICATES),
            ],
        ]);

        register_rest_route(RestRegistrar::NAMESPACE, '/verify-certificate/(?P<hash>[a-zA-Z0-9]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'verify'],
            'permission_callback' => Permissions::public(),
        ]);
    }

    public function wallet(WP_REST_Request $request): WP_REST_Response
    {
        return rest_ensure_response($this->certificates->wallet(get_current_user_id()));
    }

    public function issue(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        try {
            return new WP_REST_Response($this->certificates->issue($request->get_json_params() ?: []), 201);
        } catch (InvalidArgumentException $exception) {
            return new WP_Error('zadora_invalid_certificate', $exception->getMessage(), ['status' => 422]);
        }
    }

    public function verify(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $certificate = $this->certificates->verify((string) $request['hash']);

        return $certificate ? rest_ensure_response($certificate) : new WP_Error('zadora_certificate_not_found', 'Certificate could not be verified.', ['status' => 404]);
    }
}
