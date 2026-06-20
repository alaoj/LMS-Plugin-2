<?php

declare(strict_types=1);

namespace Zadora\Lms\Http;

use WP_REST_Request;

final class Permissions
{
    public static function capability(string $capability): callable
    {
        return static fn (WP_REST_Request $request): bool => current_user_can($capability);
    }

    public static function authenticated(): callable
    {
        return static fn (WP_REST_Request $request): bool => is_user_logged_in();
    }

    public static function public(): callable
    {
        return static fn (WP_REST_Request $request): bool => true;
    }
}
