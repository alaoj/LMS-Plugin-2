<?php

declare(strict_types=1);

namespace Zadora\Lms\Core;

use Zadora\Lms\Database\Schema;
use Zadora\Lms\Security\Capabilities;

final class Activator
{
    public static function activate(): void
    {
        (new Schema())->install();
        (new Capabilities())->install();

        update_option('zadora_lms_version', ZADORA_LMS_VERSION, false);
    }
}
