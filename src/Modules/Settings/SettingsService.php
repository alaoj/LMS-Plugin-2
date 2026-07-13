<?php

declare(strict_types=1);

namespace Zadora\Lms\Modules\Settings;

use InvalidArgumentException;

final class SettingsService
{
    private const OPTION = 'zadora_lms_settings';

    private const SECTIONS = ['branding', 'login', 'certificates', 'payments', 'ai', 'notifications'];

    public function all(bool $includeSecrets = false): array
    {
        $settings = wp_parse_args(get_option(self::OPTION, []), $this->defaults());

        return $includeSecrets ? $settings : $this->withoutSecrets($settings);
    }

    public function section(string $section, bool $includeSecrets = false): array
    {
        $section = sanitize_key($section);

        if (! in_array($section, self::SECTIONS, true)) {
            throw new InvalidArgumentException('Unknown settings section.');
        }

        $settings = $this->all($includeSecrets);

        return $settings[$section] ?? [];
    }

    public function updateSection(string $section, array $input): array
    {
        $section = sanitize_key($section);

        if (! in_array($section, self::SECTIONS, true)) {
            throw new InvalidArgumentException('Unknown settings section.');
        }

        $settings = wp_parse_args(get_option(self::OPTION, []), $this->defaults());
        $sanitized = array_filter(
            $this->sanitizeSection($section, $input),
            static fn (mixed $value): bool => $value !== null
        );
        $settings[$section] = array_replace($settings[$section] ?? [], $sanitized);

        update_option(self::OPTION, $settings, false);

        do_action('zadora_lms_settings_updated', $section, $this->withoutSecrets($settings[$section]));

        return $this->withoutSecrets($settings[$section]);
    }

    private function sanitizeSection(string $section, array $input): array
    {
        return match ($section) {
            'branding' => [
                'logo_url' => isset($input['logo_url']) ? esc_url_raw((string) $input['logo_url']) : null,
                'favicon_url' => isset($input['favicon_url']) ? esc_url_raw((string) $input['favicon_url']) : null,
                'primary_color' => $this->sanitizeHex($input['primary_color'] ?? null),
                'accent_color' => $this->sanitizeHex($input['accent_color'] ?? null),
                'font_family' => isset($input['font_family']) ? sanitize_text_field((string) $input['font_family']) : null,
            ],
            'login' => [
                'background_url' => isset($input['background_url']) ? esc_url_raw((string) $input['background_url']) : null,
                'welcome_message' => isset($input['welcome_message']) ? sanitize_textarea_field((string) $input['welcome_message']) : null,
                'social_login_ready' => ! empty($input['social_login_ready']),
            ],
            'certificates' => [
                'verification_page_id' => isset($input['verification_page_id']) ? absint($input['verification_page_id']) : null,
                'default_expiry_months' => isset($input['default_expiry_months']) ? absint($input['default_expiry_months']) : null,
            ],
            'payments' => [
                'default_currency' => $this->sanitizeCurrency($input['default_currency'] ?? null),
                'stripe_public_key' => isset($input['stripe_public_key']) ? sanitize_text_field((string) $input['stripe_public_key']) : null,
                'stripe_secret_key' => isset($input['stripe_secret_key']) ? sanitize_text_field((string) $input['stripe_secret_key']) : null,
                'paystack_public_key' => isset($input['paystack_public_key']) ? sanitize_text_field((string) $input['paystack_public_key']) : null,
                'paystack_secret_key' => isset($input['paystack_secret_key']) ? sanitize_text_field((string) $input['paystack_secret_key']) : null,
            ],
            'ai' => [
                'provider' => $this->sanitizeProvider($input['provider'] ?? null),
                'openai_api_key' => isset($input['openai_api_key']) ? sanitize_text_field((string) $input['openai_api_key']) : null,
                'claude_api_key' => isset($input['claude_api_key']) ? sanitize_text_field((string) $input['claude_api_key']) : null,
                'monthly_usage_limit' => isset($input['monthly_usage_limit']) ? absint($input['monthly_usage_limit']) : null,
            ],
            'notifications' => [
                'email_enabled' => ! empty($input['email_enabled']),
                'in_app_enabled' => array_key_exists('in_app_enabled', $input) ? ! empty($input['in_app_enabled']) : true,
                'from_name' => isset($input['from_name']) ? sanitize_text_field((string) $input['from_name']) : null,
            ],
        };
    }

    private function defaults(): array
    {
        return [
            'branding' => [
                'logo_url' => null,
                'favicon_url' => null,
                'primary_color' => '#2563eb',
                'accent_color' => '#7c3aed',
                'font_family' => null,
            ],
            'login' => [
                'background_url' => null,
                'welcome_message' => 'Welcome back to your learning workspace.',
                'social_login_ready' => false,
            ],
            'certificates' => [
                'verification_page_id' => null,
                'default_expiry_months' => null,
            ],
            'payments' => [
                'default_currency' => 'USD',
                'stripe_public_key' => null,
                'stripe_secret_key' => null,
                'paystack_public_key' => null,
                'paystack_secret_key' => null,
            ],
            'ai' => [
                'provider' => 'openai',
                'openai_api_key' => null,
                'claude_api_key' => null,
                'monthly_usage_limit' => 1000,
            ],
            'notifications' => [
                'email_enabled' => false,
                'in_app_enabled' => true,
                'from_name' => 'Zadora LMS',
            ],
        ];
    }

    private function withoutSecrets(array $settings): array
    {
        foreach (['stripe_secret_key', 'paystack_secret_key', 'openai_api_key', 'claude_api_key'] as $key) {
            if (array_key_exists($key, $settings)) {
                $settings[$key] = ! empty($settings[$key]) ? 'configured' : null;
            }
        }

        foreach ($settings as $section => $values) {
            if (is_array($values)) {
                $settings[$section] = $this->withoutSecrets($values);
            }
        }

        return $settings;
    }

    private function sanitizeHex(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        return preg_match('/^#[0-9a-fA-F]{6}$/', $value) ? strtolower($value) : null;
    }

    private function sanitizeCurrency(mixed $value): ?string
    {
        $currency = strtoupper(sanitize_text_field((string) ($value ?? '')));

        return in_array($currency, ['NGN', 'USD', 'GBP', 'EUR'], true) ? $currency : null;
    }

    private function sanitizeProvider(mixed $value): ?string
    {
        $provider = sanitize_key((string) ($value ?? ''));

        return in_array($provider, ['openai', 'claude'], true) ? $provider : null;
    }
}
