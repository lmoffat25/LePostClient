<?php

namespace LePostClient\Admin;

use LePostClient\Admin\Controller\DashboardController;
use LePostClient\Admin\Controller\IdeasListController;
use LePostClient\Admin\Controller\SettingsController;

class AdminMenu {

    public DashboardController $dashboard_controller;
    public IdeasListController $ideas_list_controller;
    public SettingsController $settings_controller;

    private string $main_menu_slug = 'lepostclient_dashboard';

    public function __construct(
        DashboardController $dashboard_controller,
        IdeasListController $ideas_list_controller,
        SettingsController $settings_controller
    ) {
        $this->dashboard_controller = $dashboard_controller;
        $this->ideas_list_controller = $ideas_list_controller;
        $this->settings_controller = $settings_controller;
    }

    public function register_menus() {
        // Main Dashboard Menu Page
        add_menu_page(
            __('Le Post Client Dashboard', 'lepostclient'), // Page Title
            __('Le Post Client', 'lepostclient'),          // Menu Title
            'manage_options',                             // Capability
            $this->main_menu_slug,                        // Menu Slug
            [$this->dashboard_controller, 'render_page'], // Callback function to render the page
            'dashicons-cloud-upload',                     // Icon URL (WordPress Dashicon)
            75                                            // Position
        );

        // Submenu Page: Ideas List
        add_submenu_page(
            $this->main_menu_slug,                        // Parent Slug
            __('Ideas List', 'lepostclient'),             // Page Title
            __('Ideas List', 'lepostclient'),             // Menu Title
            'manage_options',                             // Capability
            'lepostclient_ideas_list',                    // Menu Slug
            [$this->ideas_list_controller, 'render_page'] // Callback
        );

        // Submenu Page: Settings
        add_submenu_page(
            $this->main_menu_slug,                        // Parent Slug
            __('Settings', 'lepostclient'),               // Page Title
            __('Settings', 'lepostclient'),               // Menu Title
            'manage_options',                             // Capability
            'lepostclient_settings',                      // Menu Slug
            [$this->settings_controller, 'render_page']   // Callback
        );
    }
} 