<?php

declare(strict_types=1);

namespace Zadora\Lms\Modules\Notifications;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use Zadora\Lms\Http\ControllerInterface;
use Zadora\Lms\Http\Permissions;
use Zadora\Lms\Http\RestRegistrar;

final class NotificationController implements ControllerInterface
{
    public function __construct(private readonly NotificationService $notifications)
    {
    }

    public function registerRoutes(): void
    {
        register_rest_route(RestRegistrar::NAMESPACE, '/notifications', [
            'methods' => 'GET',
            'callback' => [$this, 'index'],
            'permission_callback' => Permissions::authenticated(),
        ]);

        register_rest_route(RestRegistrar::NAMESPACE, '/notifications/(?P<id>\d+)/read', [
            'methods' => 'PATCH',
            'callback' => [$this, 'read'],
            'permission_callback' => Permissions::authenticated(),
        ]);

        register_rest_route(RestRegistrar::NAMESPACE, '/notifications/read-all', [
            'methods' => 'PATCH',
            'callback' => [$this, 'readAll'],
            'permission_callback' => Permissions::authenticated(),
        ]);
    }

    public function index(WP_REST_Request $request): WP_REST_Response
    {
        return rest_ensure_response($this->notifications->listForUser(
            get_current_user_id(),
            (bool) $request->get_param('unread')
        ));
    }

    public function read(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $marked = $this->notifications->markRead((int) $request['id'], get_current_user_id());

        return $marked ? rest_ensure_response(['read' => true]) : new WP_Error('zadora_notification_not_found', 'Notification not found.', ['status' => 404]);
    }

    public function readAll(WP_REST_Request $request): WP_REST_Response
    {
        $this->notifications->markAllRead(get_current_user_id());

        return rest_ensure_response(['read' => true]);
    }
}
