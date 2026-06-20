<?php

declare(strict_types=1);

namespace Zadora\Lms\Core;

final class Autoloader
{
    public static function register(string $prefix, string $baseDirectory): void
    {
        spl_autoload_register(static function (string $class) use ($prefix, $baseDirectory): void {
            $length = strlen($prefix);

            if (strncmp($prefix, $class, $length) !== 0) {
                return;
            }

            $relativeClass = substr($class, $length);
            $file = rtrim($baseDirectory, '/\\') . DIRECTORY_SEPARATOR
                . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';

            if (is_readable($file)) {
                require $file;
            }
        });
    }
}
