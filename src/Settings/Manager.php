<?php

declare(strict_types=1);

namespace LePostClient\Settings;

/**
 * Settings Manager
 * 
 * Handles all plugin settings management including API credentials,
 * content generation preferences, and caching of account information.
 *
 * @since 1.0.0
 */
class Manager {

    /**
     * Settings option names
     */
    const API_KEY_OPTION = 'lepostclient_api_key';
    const COMPANY_INFO_OPTION = 'lepostclient_company_info';
    const WRITING_STYLE_OPTION = 'lepostclient_writing_style';
    const API_TIMEOUT_OPTION = 'lepostclient_api_timeout';
    const AVAILABLE_CREDITS_OPTION = 'lepostclient_available_credits';
    const CREDITS_CACHE_TRANSIENT = 'lepostclient_credits_cache';
    const CREDITS_CACHE_EXPIRY = HOUR_IN_SECONDS; // 1 hour caching as per requirements

    /**
     * Constructor
     * 
     * @since 1.0.0
     */
    public function __construct() {
        add_action('admin_init', [$this, 'register_settings']);
    }

    /**
     * Register settings with WordPress Settings API
     * 
     * @since 1.0.0
     * 
     * @return void
     */
    public function register_settings(): void {
        register_setting(
            'lepostclient_settings_group',
            self::API_KEY_OPTION,
            [
                'type' => 'string',
                'sanitize_callback' => [$this, 'sanitize_api_key'],
                'default' => '',
            ]
        );
        
        register_setting(
            'lepostclient_settings_group',
            self::COMPANY_INFO_OPTION,
            [
                'type' => 'string',
                'sanitize_callback' => [$this, 'sanitize_textarea'],
                'default' => '',
            ]
        );
        
        register_setting(
            'lepostclient_settings_group',
            self::WRITING_STYLE_OPTION,
            [
                'type' => 'string',
                'sanitize_callback' => [$this, 'sanitize_textarea'],
                'default' => '',
            ]
        );
        
        register_setting(
            'lepostclient_settings_group',
            self::API_TIMEOUT_OPTION,
            [
                'type' => 'integer',
                'sanitize_callback' => [$this, 'sanitize_timeout'],
                'default' => 300, // 5 minutes default as per requirements
            ]
        );

        // Add settings sections and fields
        add_settings_section(
            'lepostclient_main_section',
            __('API Settings', 'lepostclient'),
            [$this, 'render_main_section'],
            'lepostclient'
        );

        add_settings_field(
            self::API_KEY_OPTION,
            __('API Key', 'lepostclient'),
            [$this, 'render_api_key_field'],
            'lepostclient',
            'lepostclient_main_section'
        );
        
        add_settings_field(
            self::API_TIMEOUT_OPTION,
            __('API Timeout (seconds)', 'lepostclient'),
            [$this, 'render_timeout_field'],
            'lepostclient',
            'lepostclient_main_section'
        );
        
        add_settings_field(
            self::COMPANY_INFO_OPTION,
            __('Company Information', 'lepostclient'),
            [$this, 'render_company_info_field'],
            'lepostclient',
            'lepostclient_main_section'
        );
        
        add_settings_field(
            self::WRITING_STYLE_OPTION,
            __('Writing Style', 'lepostclient'),
            [$this, 'render_writing_style_field'],
            'lepostclient',
            'lepostclient_main_section'
        );
    }

    /**
     * Sanitize API key input
     * 
     * @since 1.0.0
     * 
     * @param string $input The input to sanitize
     * @return string Sanitized input
     */
    public function sanitize_api_key(string $input): string {
        return sanitize_text_field($input);
    }

    /**
     * Sanitize textarea input
     * 
     * @since 1.0.0
     * 
     * @param string $input The input to sanitize
     * @return string Sanitized input
     */
    public function sanitize_textarea(string $input): string {
        return sanitize_textarea_field($input);
    }

    /**
     * Sanitize timeout input
     * 
     * @since 1.0.0
     * 
     * @param mixed $input The input to sanitize
     * @return int Sanitized input
     */
    public function sanitize_timeout($input): int {
        $timeout = absint($input);
        // Ensure minimum timeout of 30 seconds and maximum of 600 seconds (10 minutes)
        return min(max($timeout, 30), 600);
    }

    /**
     * Render main settings section
     * 
     * @since 1.0.0
     * 
     * @return void
     */
    public function render_main_section(): void {
        echo '<p>' . esc_html__('Configure your LePost API connection settings below.', 'lepostclient') . '</p>';
    }

    /**
     * Render API key field
     * 
     * @since 1.0.0
     * 
     * @return void
     */
    public function render_api_key_field(): void {
        $api_key = $this->get_api_key();
        $masked_key = empty($api_key) ? '' : substr($api_key, 0, 4) . '••••••••••••••';
        
        echo '<input type="password" id="' . esc_attr(self::API_KEY_OPTION) . '" name="' . esc_attr(self::API_KEY_OPTION) . '" value="' . esc_attr($api_key) . '" class="regular-text" />';
        echo '<p class="description">' . esc_html__('Your LePost API key for authentication', 'lepostclient') . '</p>';
    }

    /**
     * Render timeout field
     * 
     * @since 1.0.0
     * 
     * @return void
     */
    public function render_timeout_field(): void {
        $timeout = $this->get_api_timeout();
        
        echo '<input type="number" id="' . esc_attr(self::API_TIMEOUT_OPTION) . '" name="' . esc_attr(self::API_TIMEOUT_OPTION) . '" value="' . esc_attr((string)$timeout) . '" min="30" max="600" class="small-text" />';
        echo '<p class="description">' . esc_html__('Timeout in seconds for API requests (between 30-600 seconds)', 'lepostclient') . '</p>';
    }

    /**
     * Render company info field
     * 
     * @since 1.0.0
     * 
     * @return void
     */
    public function render_company_info_field(): void {
        $company_info = $this->get_company_info();
        
        echo '<textarea id="' . esc_attr(self::COMPANY_INFO_OPTION) . '" name="' . esc_attr(self::COMPANY_INFO_OPTION) . '" rows="5" cols="50" class="large-text">' . esc_textarea($company_info) . '</textarea>';
        echo '<p class="description">' . esc_html__('Information about your company to use in generated content', 'lepostclient') . '</p>';
    }

    /**
     * Render writing style field
     * 
     * @since 1.0.0
     * 
     * @return void
     */
    public function render_writing_style_field(): void {
        $writing_style = $this->get_writing_style();
        
        echo '<textarea id="' . esc_attr(self::WRITING_STYLE_OPTION) . '" name="' . esc_attr(self::WRITING_STYLE_OPTION) . '" rows="5" cols="50" class="large-text">' . esc_textarea($writing_style) . '</textarea>';
        echo '<p class="description">' . esc_html__('Preferred writing style for generated content', 'lepostclient') . '</p>';
    }

    /**
     * Get API key
     * 
     * @since 1.0.0
     * 
     * @return string The API key or empty string
     */
    public function get_api_key(): string {
        return (string) get_option(self::API_KEY_OPTION, '');
    }

    /**
     * Save API key
     * 
     * @since 1.0.0
     * 
     * @param string $api_key The API key to save
     * @return bool Whether the option was updated
     * @throws \InvalidArgumentException If the API key is empty
     */
    public function save_api_key(string $api_key): bool {
        if (empty($api_key)) {
            throw new \InvalidArgumentException(__('API key cannot be empty', 'lepostclient'));
        }
        
        // Verify nonce and user capabilities in the calling function
        return update_option(self::API_KEY_OPTION, sanitize_text_field($api_key));
    }

    /**
     * Get company info
     * 
     * @since 1.0.0
     * 
     * @return string The company info or empty string
     */
    public function get_company_info(): string {
        return (string) get_option(self::COMPANY_INFO_OPTION, '');
    }

    /**
     * Save company info
     * 
     * @since 1.0.0
     * 
     * @param string $company_info The company info to save
     * @return bool Whether the option was updated
     */
    public function save_company_info(string $company_info): bool {
        // Verify nonce and user capabilities in the calling function
        return update_option(self::COMPANY_INFO_OPTION, sanitize_textarea_field($company_info));
    }

    /**
     * Get writing style
     * 
     * @since 1.0.0
     * 
     * @return string The writing style or empty string
     */
    public function get_writing_style(): string {
        return (string) get_option(self::WRITING_STYLE_OPTION, '');
    }

    /**
     * Save writing style
     * 
     * @since 1.0.0
     * 
     * @param string $writing_style The writing style to save
     * @return bool Whether the option was updated
     */
    public function save_writing_style(string $writing_style): bool {
        // Verify nonce and user capabilities in the calling function
        return update_option(self::WRITING_STYLE_OPTION, sanitize_textarea_field($writing_style));
    }

    /**
     * Get API timeout
     * 
     * @since 1.0.0
     * 
     * @return int Timeout in seconds
     */
    public function get_api_timeout(): int {
        return (int) get_option(self::API_TIMEOUT_OPTION, 300);
    }

    /**
     * Save API timeout
     * 
     * @since 1.0.0
     * 
     * @param int $timeout Timeout in seconds
     * @return bool Whether the option was updated
     */
    public function save_api_timeout(int $timeout): bool {
        $sanitized_timeout = $this->sanitize_timeout($timeout);
        return update_option(self::API_TIMEOUT_OPTION, $sanitized_timeout);
    }

    /**
     * Get available credits with caching
     * 
     * @since 1.0.0
     * 
     * @param bool $bypass_cache Whether to bypass the cache and fetch fresh data
     * @return int Number of available credits
     */
    public function get_available_credits(bool $bypass_cache = false): int {
        if (!$bypass_cache) {
            $cached_credits = get_transient(self::CREDITS_CACHE_TRANSIENT);
            if (false !== $cached_credits) {
                return (int) $cached_credits;
            }
        }

        $credits = (int) get_option(self::AVAILABLE_CREDITS_OPTION, 0);
        
        // Cache the credits for 1 hour
        set_transient(self::CREDITS_CACHE_TRANSIENT, $credits, self::CREDITS_CACHE_EXPIRY);
        
        return $credits;
    }

    /**
     * Save available credits and update cache
     * 
     * @since 1.0.0
     * 
     * @param int $credits Number of available credits
     * @return bool Whether the option was updated
     */
    public function save_available_credits(int $credits): bool {
        $result = update_option(self::AVAILABLE_CREDITS_OPTION, $credits);
        
        if ($result) {
            set_transient(self::CREDITS_CACHE_TRANSIENT, $credits, self::CREDITS_CACHE_EXPIRY);
        }
        
        return $result;
    }

    /**
     * Check if all required settings are configured
     * 
     * @since 1.0.0
     * 
     * @return bool True if all required settings are configured
     */
    public function is_configured(): bool {
        return !empty($this->get_api_key());
    }
} 