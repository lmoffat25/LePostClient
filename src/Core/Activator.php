<?php

namespace LePostClient\Core;

use LePostClient\Settings\Manager as SettingsManager;

class Activator {
    public static function activate() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'lepostclient_post_ideas';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NULL,
            subject TEXT NOT NULL,
            description LONGTEXT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            api_theme_source VARCHAR(255) NULL,
            generated_post_id BIGINT UNSIGNED NULL,
            creation_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            last_modified_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY status (status),
            KEY user_id (user_id),
            KEY generated_post_id (generated_post_id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        // Set up default options or perform other activation tasks if needed
        if (get_option('lepostclient_plugin_version') === false) {
            update_option('lepostclient_plugin_version', LEPOCLIENT_VERSION);
        }
        if (get_option(SettingsManager::API_KEY_OPTION) === false) {
             update_option(SettingsManager::API_KEY_OPTION, '');
        }
        if (get_option(SettingsManager::COMPANY_INFO_OPTION) === false) {
             update_option(SettingsManager::COMPANY_INFO_OPTION, '');
        }
        if (get_option(SettingsManager::WRITING_STYLE_OPTION) === false) {
             update_option(SettingsManager::WRITING_STYLE_OPTION, '');
        }

        // Flush rewrite rules if you register custom post types or taxonomies (not in this scope yet)
        // flush_rewrite_rules();
    }
} 