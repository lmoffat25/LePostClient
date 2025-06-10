<?php

namespace LePostClient\Settings;

class Manager {

    const API_KEY_OPTION = 'lepostclient_api_key';
    const COMPANY_INFO_OPTION = 'lepostclient_company_info';
    const WRITING_STYLE_OPTION = 'lepostclient_writing_style';
    const AVAILABLE_CREDITS_OPTION = 'lepostclient_available_credits'; // Example other option

    public function __construct() {
        // Constructor can be used to register settings if using WordPress Settings API
        // add_action('admin_init', [$this, 'register_settings']);
    }

    /* Example using WordPress Settings API (more involved, often used for complex settings pages)
    public function register_settings() {
        register_setting(
            'lepostclient_settings_group', // Option group
            self::API_KEY_OPTION,          // Option name
            [$this, 'sanitize_api_key'] // Sanitization callback
        );
        register_setting('lepostclient_settings_group', self::COMPANY_INFO_OPTION, [$this, 'sanitize_textarea']);
        register_setting('lepostclient_settings_group', self::WRITING_STYLE_OPTION, [$this, 'sanitize_textarea']);
        // Add more settings fields/sections as needed for the Settings API...
    }

    public function sanitize_api_key($input) {
        return sanitize_text_field($input);
    }
    public function sanitize_textarea($input) {
        return sanitize_textarea_field($input);
    }
    */

    public function get_api_key(): string {
        return (string) get_option(self::API_KEY_OPTION, '');
    }

    public function save_api_key(string $api_key): bool {
        // You might want to validate or further sanitize the key here if not done elsewhere
        return update_option(self::API_KEY_OPTION, sanitize_text_field($api_key));
    }

    public function get_company_info(): string {
        return (string) get_option(self::COMPANY_INFO_OPTION, '');
    }

    public function save_company_info(string $company_info): bool {
        return update_option(self::COMPANY_INFO_OPTION, sanitize_textarea_field($company_info));
    }

    public function get_writing_style(): string {
        return (string) get_option(self::WRITING_STYLE_OPTION, '');
    }

    public function save_writing_style(string $writing_style): bool {
        return update_option(self::WRITING_STYLE_OPTION, sanitize_textarea_field($writing_style));
    }

    public function get_available_credits(): int {
        return (int) get_option(self::AVAILABLE_CREDITS_OPTION, 0);
    }

    public function save_available_credits(int $credits): bool {
        return update_option(self::AVAILABLE_CREDITS_OPTION, $credits);
    }

    // Add more getter/setter methods for other options as needed
} 