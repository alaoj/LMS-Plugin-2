<?php
/**
 * Plugin Name: Zadora LMS
 * Description: Premium workforce learning, certification, and compliance platform for WordPress.
 * Version: 0.1.0
 * Requires PHP: 8.3
 * Author: Zadora
 * Text Domain: zadora-lms
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

define('ZADORA_LMS_VERSION', '0.1.0');
define('ZADORA_LMS_FILE', __FILE__);
define('ZADORA_LMS_PATH', plugin_dir_path(__FILE__));
define('ZADORA_LMS_URL', plugin_dir_url(__FILE__));

require_once ZADORA_LMS_PATH . 'src/Core/Autoloader.php';

\Zadora\Lms\Core\Autoloader::register('Zadora\\Lms\\', ZADORA_LMS_PATH . 'src/');

register_activation_hook(__FILE__, static function (): void {
    \Zadora\Lms\Core\Activator::activate();
});

add_action('plugins_loaded', static function (): void {
    \Zadora\Lms\Core\Plugin::instance()->boot();
});
