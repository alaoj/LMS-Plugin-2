<?php

declare(strict_types=1);

namespace Zadora\Lms\Core;

use Zadora\Lms\Http\RestRegistrar;
use Zadora\Lms\Modules\Certificates\CertificateModule;
use Zadora\Lms\Modules\Courses\CourseModule;
use Zadora\Lms\Modules\Enrollments\EnrollmentModule;
use Zadora\Lms\Modules\Payments\PaymentModule;
use Zadora\Lms\Security\Capabilities;
use Zadora\Lms\Support\Container;

final class Plugin
{
    private static ?self $instance = null;

    private bool $booted = false;

    private Container $container;

    /** @var ModuleInterface[] */
    private array $modules;

    private function __construct()
    {
        $this->container = new Container();
        $this->modules = [
            new CourseModule(),
            new EnrollmentModule(),
            new CertificateModule(),
            new PaymentModule(),
        ];
    }

    public static function instance(): self
    {
        if (! self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        $this->container->singleton(Capabilities::class, static fn (): Capabilities => new Capabilities());
        $this->container->singleton(RestRegistrar::class, static fn (): RestRegistrar => new RestRegistrar());

        foreach ($this->modules as $module) {
            $module->register($this->container);
        }

        add_action('rest_api_init', function (): void {
            $this->container->get(RestRegistrar::class)->register();
        });

        add_action('wp_enqueue_scripts', [$this, 'enqueueFrontend']);
        add_action('init', [Shortcodes::class, 'register']);

        foreach ($this->modules as $module) {
            $module->boot($this->container);
        }

        $this->booted = true;
    }

    public function enqueueFrontend(): void
    {
        $asset = ZADORA_LMS_PATH . 'assets/dist/app.asset.php';
        $metadata = is_readable($asset) ? require $asset : ['dependencies' => ['wp-element'], 'version' => ZADORA_LMS_VERSION];

        wp_enqueue_style(
            'zadora-lms-app',
            ZADORA_LMS_URL . 'assets/dist/app.css',
            [],
            $metadata['version'] ?? ZADORA_LMS_VERSION
        );

        wp_enqueue_script(
            'zadora-lms-app',
            ZADORA_LMS_URL . 'assets/dist/app.js',
            $metadata['dependencies'] ?? ['wp-element'],
            $metadata['version'] ?? ZADORA_LMS_VERSION,
            true
        );

        wp_localize_script('zadora-lms-app', 'zadoraLms', [
            'apiRoot' => esc_url_raw(rest_url('zadora/v1')),
            'nonce' => wp_create_nonce('wp_rest'),
            'currentUserId' => get_current_user_id(),
        ]);
    }
}
