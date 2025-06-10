<?php

namespace LePostClient\Admin\Controller;

use LePostClient\Api\Client as ApiClient;
use LePostClient\Settings\Manager as SettingsManager;

class DashboardController {

    private ApiClient $api_client;
    private SettingsManager $settings_manager;

    public function __construct(ApiClient $api_client, SettingsManager $settings_manager) {
        $this->api_client = $api_client;
        $this->settings_manager = $settings_manager;
    }

    public function render_page() {
        $account_info = null;
        $error_message = '';

        $api_key = $this->settings_manager->get_api_key();

        if (!empty($api_key)) {
            $response = $this->api_client->get_account_info();
            
            if (isset($response['is_valid']) && $response['is_valid'] === true) {
                $account_info = $response;
            } else {
                $error_message = $response['message'] ?? __('An unknown error occurred while fetching account information.', 'lepostclient');
            }
        } else {
            $error_message = __('API Key is not set. Please configure it in the settings page.', 'lepostclient');
        }

        $view_path = dirname(__FILE__, 2) . '/View/dashboard-page.php';
        if (file_exists($view_path)) {
            include $view_path;
        } else {
            echo '<div class="error"><p>Dashboard view file not found.</p></div>';
        }
    }
} 