<?php

namespace LePostClient\Admin\Controller;

use LePostClient\Settings\Manager as SettingsManager;
use LePostClient\Api\Client as ApiClient;

class SettingsController {

    private SettingsManager $settings_manager;
    private ApiClient $api_client;

    public function __construct(SettingsManager $settings_manager, ApiClient $api_client) {
        $this->settings_manager = $settings_manager;
        $this->api_client = $api_client;
    }

    public function render_page() {

        $view_path = dirname(__FILE__, 2) . '/View/settings-page.php';
        if (file_exists($view_path)) {
            // extract(compact('api_key'));
            include $view_path;
        } else {
            echo '<div class="error"><p>Settings view file not found.</p></div>';
        }
    }

    public function handle_save_settings() {
        // Check if the user has the required capability
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'lepostclient' ) );
        }

        // Verify nonce
        if ( ! isset( $_POST['lepostclient_settings_nonce_field'] ) || ! wp_verify_nonce( sanitize_text_field(wp_unslash($_POST['lepostclient_settings_nonce_field'])), 'lepostclient_save_settings_action' ) ) {
            wp_die( esc_html__( 'Nonce verification failed.', 'lepostclient' ) );
        }

        $settings_updated = false;

        // Sanitize and save API key
        if ( isset( $_POST['lepostclient_api_key'] ) ) {
            $new_api_key = sanitize_text_field( wp_unslash( $_POST['lepostclient_api_key'] ) );
            // Optionally validate API key here using $this->api_client->validateApiKey($new_api_key)
            // and only save if valid, or save and show a different notice.
            $this->settings_manager->save_api_key($new_api_key);
            $settings_updated = true;
        }

        // Sanitize and save Company Information
        if ( isset( $_POST['lepostclient_company_info'] ) ) {
            $company_info = sanitize_textarea_field( wp_unslash( $_POST['lepostclient_company_info'] ) );
            $this->settings_manager->save_company_info($company_info);
            $settings_updated = true;
        }

        // Sanitize and save Writing Style
        if ( isset( $_POST['lepostclient_writing_style'] ) ) {
            $writing_style = sanitize_textarea_field( wp_unslash( $_POST['lepostclient_writing_style'] ) );
            $this->settings_manager->save_writing_style($writing_style);
            $settings_updated = true;
        }

        if ($settings_updated) {
            add_settings_error('lepostclient_settings_notices', 'settings_saved', __('Settings saved successfully.', 'lepostclient'), 'updated');
        }

        // Store notices for display after redirect
        set_transient('settings_errors', get_settings_errors(), 30);

        // Redirect back to the settings page
        $redirect_url = admin_url( 'admin.php?page=lepostclient_settings' );
        wp_redirect( $redirect_url );
        exit;
    }
} 