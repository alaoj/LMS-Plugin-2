<?php

declare(strict_types=1);

namespace Zadora\Lms\Support;

final class Arr
{
    /** @param array<string, mixed> $data */
    public static function only(array $data, array $keys): array
    {
        return array_intersect_key($data, array_flip($keys));
    }
}
