<?php

declare(strict_types=1);

namespace Zadora\Lms\Security;

use WP_User;

abstract class Policy
{
    protected function has(WP_User $user, string $capability): bool
    {
        return user_can($user, $capability);
    }
}
