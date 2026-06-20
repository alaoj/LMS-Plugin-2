<?php

declare(strict_types=1);

namespace Zadora\Lms\Support;

final class Clock
{
    public function now(): string
    {
        return current_time('mysql', true);
    }
}
