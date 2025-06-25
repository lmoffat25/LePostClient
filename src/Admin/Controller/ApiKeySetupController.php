<?php

declare(strict_types=1);

namespace LePostClient\Admin\Controller;

use LePostClient\Settings\Manager as SettingsManager;

/**
 * Controller for the API key setup screen
 * 
 * @since 1.0.3
 */
class ApiKeySetupController {

    private SettingsManager $settings_manager;

    /**
     * Constructor
     * 
     * @since 1.0.3
     * 
     * @param SettingsManager $settings_manager The settings manager
     */
    public function __construct(SettingsManager $settings_manager) {
        $this->settings_manager = $settings_manager;
    }

    /**
     * Render the API key setup screen
     * 
     * @since 1.0.3
     * 
     * @return void
     */
    public function render_page(): void {
        $view_path = dirname(__FILE__, 2) . '/View/api-key-setup-page.php';
        if (file_exists($view_path)) {
            include $view_path;
        } else {
            echo '<div class="error"><p>API key setup view file not found.</p></div>';
        }
    }

    /**
     * Handle saving the API key
     * 
     * @since 1.0.3
     * 
     * @return void
     */
    public function handle_save_api_key(): void {
        // Check if the user has the required capability
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'lepostclient'));
        }

        // Verify nonce
        if (!isset($_POST['lepostclient_api_key_setup_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['lepostclient_api_key_setup_nonce'])), 'lepostclient_save_api_key_action')) {
            wp_die(esc_html__('Nonce verification failed.', 'lepostclient'));
        }

        $api_key_updated = false;

        // Sanitize and save API key
        if (isset($_POST['lepostclient_api_key'])) {
            $new_api_key = sanitize_text_field(wp_unslash($_POST['lepostclient_api_key']));
            
            if (empty($new_api_key)) {
                add_settings_error(
                    'lepostclient_api_key_setup_notices',
                    'api_key_empty',
                    __('API key cannot be empty.', 'lepostclient'),
                    'error'
                );
            } else {
                update_option(\LePostClient\Settings\Manager::API_KEY_OPTION, $new_api_key);
                $api_key_updated = true;
            }
        }

        if ($api_key_updated) {
            add_settings_error(
                'lepostclient_api_key_setup_notices',
                'api_key_saved',
                __('API key saved successfully.', 'lepostclient'),
                'updated'
            );
        }

        // Store notices for display after redirect
        set_transient('settings_errors', get_settings_errors(), 30);

        // Redirect to the referring page
        $redirect_url = wp_get_referer() ?: admin_url();
        wp_safe_redirect($redirect_url);
        exit;
    }
} 