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
            status_changed DATETIME NULL,
            api_theme_source VARCHAR(255) NULL,
            generated_post_id BIGINT UNSIGNED NULL,
            task_id VARCHAR(255) NULL,
            creation_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            last_modified_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY status (status),
            KEY user_id (user_id),
            KEY generated_post_id (generated_post_id),
            KEY task_id (task_id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        // Check if we need to add the status_changed column (for existing installations)
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'status_changed'");
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN status_changed DATETIME NULL AFTER status");
            // Initialize status_changed for existing records
            $wpdb->query("UPDATE $table_name SET status_changed = last_modified_date WHERE status_changed IS NULL");
        }

        // Check if we need to add the task_id column (for existing installations)
        $task_id_column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'task_id'");
        if (empty($task_id_column_exists)) {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN task_id VARCHAR(255) NULL AFTER generated_post_id");
        }

        // Set up default options or perform other activation tasks if needed
        if (get_option('lepostclient_plugin_version') === false) {
            update_option('lepostclient_plugin_version', LEPOSTCLIENT_VERSION);
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