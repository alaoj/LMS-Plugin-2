<?php

declare(strict_types=1);

namespace Zadora\Lms\Modules\Announcements;

use InvalidArgumentException;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use Zadora\Lms\Http\ControllerInterface;
use Zadora\Lms\Http\Permissions;
use Zadora\Lms\Http\RestRegistrar;
use Zadora\Lms\Security\Capabilities;

final class AnnouncementController implements ControllerInterface
{
    public function __construct(private readonly AnnouncementService $announcements)
    {
    }

    public function registerRoutes(): void
    {
        register_rest_route(RestRegistrar::NAMESPACE, '/announcements', [
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

        register_rest_route(RestRegistrar::NAMESPACE, '/announcements/(?P<id>\d+)', [
            'methods' => 'PATCH',
            'callback' => [$this, 'update'],
            'permission_callback' => Permissions::capability(Capabilities::MANAGE_COURSES),
        ]);
    }

    public function index(WP_REST_Request $request): WP_REST_Response
    {
        return rest_ensure_response($this->announcements->list([
            'scope_type' => $request->get_param('scope_type'),
            'scope_id' => $request->get_param('scope_id'),
        ]));
    }

    public function store(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        try {
            return new WP_REST_Response($this->announcements->create($request->get_json_params() ?: [], get_current_user_id()), 201);
        } catch (InvalidArgumentException $exception) {
            return new WP_Error('zadora_invalid_announcement', $exception->getMessage(), ['status' => 422]);
        }
    }

    public function update(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        try {
            $announcement = $this->announcements->update((int) $request['id'], $request->get_json_params() ?: []);

            return $announcement ? rest_ensure_response($announcement) : new WP_Error('zadora_announcement_not_found', 'Announcement not found.', ['status' => 404]);
        } catch (InvalidArgumentException $exception) {
            return new WP_Error('zadora_invalid_announcement', $exception->getMessage(), ['status' => 422]);
        }
    }
}
