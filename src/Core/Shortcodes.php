<?php

declare(strict_types=1);

namespace Zadora\Lms\Core;

final class Shortcodes
{
    public static function register(): void
    {
        add_shortcode('zadora_app', [self::class, 'app']);
        add_shortcode('zadora_courses', [self::class, 'courses']);
        add_shortcode('zadora_my_courses', [self::class, 'myCourses']);
        add_shortcode('zadora_certificates', [self::class, 'certificates']);
    }

    public static function app(array $attributes = []): string
    {
        $view = sanitize_key((string) ($attributes['view'] ?? 'dashboard'));

        return sprintf('<div class="zadora-lms-root" data-view="%s"></div>', esc_attr($view));
    }

    public static function courses(array $attributes = []): string
    {
        $status = sanitize_key((string) ($attributes['status'] ?? ''));
        $category = sanitize_key((string) ($attributes['category'] ?? ''));

        return sprintf(
            '<div class="zadora-lms-root" data-view="courses" data-status="%s" data-category="%s"></div>',
            esc_attr($status),
            esc_attr($category)
        );
    }

    public static function myCourses(): string
    {
        return '<div class="zadora-lms-root" data-view="my-courses"></div>';
    }

    public static function certificates(): string
    {
        return '<div class="zadora-lms-root" data-view="certificates"></div>';
    }
}
