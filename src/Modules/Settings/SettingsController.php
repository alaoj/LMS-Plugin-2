<?php

declare(strict_types=1);

namespace Zadora\Lms\Modules\Settings;

use InvalidArgumentException;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use Zadora\Lms\Http\ControllerInterface;
use Zadora\Lms\Http\Permissions;
use Zadora\Lms\Http\RestRegistrar;
use Zadora\Lms\Security\Capabilities;

final class SettingsController implements ControllerInterface
{
    public function __construct(private readonly SettingsService $settings)
    {
    }

    public function registerRoutes(): void
    {
        register_rest_route(RestRegistrar::NAMESPACE, '/settings', [
            'methods' => 'GET',
            'callback' => [$this, 'index'],
            'permission_callback' => Permissions::capability(Capabilities::MANAGE_PLATFORM),
        ]);

        register_rest_route(RestRegistrar::NAMESPACE, '/settings/(?P<section>[a-z_]+)', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'show'],
                'permission_callback' => Permissions::capability(Capabilities::MANAGE_PLATFORM),
            ],
            [
                'methods' => 'PATCH',
                'callback' => [$this, 'update'],
                'permission_callback' => Permissions::capability(Capabilities::MANAGE_PLATFORM),
            ],
        ]);
    }

    public function index(WP_REST_Request $request): WP_REST_Response
    {
        return rest_ensure_response($this->settings->all());
    }

    public function show(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        try {
            return rest_ensure_response($this->settings->section((string) $request['section']));
        } catch (InvalidArgumentException $exception) {
            return new WP_Error('zadora_unknown_settings_section', $exception->getMessage(), ['status' => 404]);
        }
    }

    public function update(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        try {
            return rest_ensure_response($this->settings->updateSection((string) $request['section'], $request->get_json_params() ?: []));
        } catch (InvalidArgumentException $exception) {
            return new WP_Error('zadora_invalid_settings', $exception->getMessage(), ['status' => 422]);
        }
    }
}
