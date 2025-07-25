<?php
/**
 * Plugin Name: Le Post Client
 * Plugin URI:  https://leonmoffat.com/lepostclient
 * Description: A client plugin to generate posts from an API.
 * Version:     1.0.4
 * Author:      Leon Moffat
 * Author URI:  https://leonmoffat.com
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: lepostclient
 */

// Define plugin version constant
if (!defined('LEPOSTCLIENT_VERSION')) {
    define('LEPOSTCLIENT_VERSION', '1.0.4');
}

// Define API endpoint URL constant
if (!defined('LEPOSTCLIENT_API_BASE_URL')) {
    define('LEPOSTCLIENT_API_BASE_URL', 'https://agence-web-prism.fr/wp-json/le-post/v1');
}

// Define plugin URL constant
if (!defined('LEPOSTCLIENT_URL')) {
    define('LEPOSTCLIENT_URL', plugin_dir_url(__FILE__));
}

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Require the Composer autoloader.
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} else {
    // Handle the case where the autoloader is missing, maybe display an admin notice.
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('Le Post Client: Composer autoloader not found. Please run "composer install".');
    }
}

/**
 * Global exception handler for LePostClient exceptions.
 *
 * This handler will log all uncaught exceptions and display an admin notice when appropriate.
 *
 * @param \Throwable $exception The uncaught exception
 * @return void
 */
function lepostclient_exception_handler(\Throwable $exception)
{
    // Log the exception
    error_log('LePostClient Uncaught Exception: ' . $exception->getMessage());
    // Only show detailed error information in admin area when debugging is enabled
    if (\is_admin() && defined('WP_DEBUG') && WP_DEBUG) {
        $error = '<div class="error notice">';
        $error .= '<p><strong>LePostClient Error:</strong> ' . esc_html($exception->getMessage()) . '</p>';
        // Add file and line information for debugging
        if ($exception instanceof \LePostClient\Exceptions\ExceptionInterface || WP_DEBUG) {
            $error .= '<p>File: ' . esc_html($exception->getFile()) . ' (Line: ' . esc_html($exception->getLine()) . ')</p>';
        }
        $error .= '</div>';
        // This will only work for admin pages, not for AJAX or API requests
        add_action('admin_notices', function () use ($error) {
            echo $error;
        });
    }
    // For AJAX requests, we can format an error response
    if (\wp_doing_ajax()) {
        wp_send_json_error([
            'message' => 'An unexpected error occurred.',
            'details' => WP_DEBUG ? $exception->getMessage() : null
        ]);
        exit;
    }
}

// Register the exception handler
set_exception_handler('lepostclient_exception_handler');

/**
 * The code that runs during plugin activation.
 */
function activate_le_post_client()
{
    LePostClient\Core\Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_le_post_client()
{
    LePostClient\Core\Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_le_post_client');
register_deactivation_hook(__FILE__, 'deactivate_le_post_client');

// Configure Plugin Update Checker
add_action('init', function () {
    // Ensure the plugin update checker class exists
    if (!class_exists('YahnisElsts\PluginUpdateChecker\v5\PucFactory')) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Le Post Client: Plugin Update Checker library not found. Please run "composer install".');
        }
        return;
    }
    
    try {
        $myUpdateChecker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
            'https://github.com/lmoffat25/LePostClient.git',
            __FILE__,
            'lepostclient'
        );

        // Optional: Configure to use GitHub release assets
        $myUpdateChecker->getVcsApi()->enableReleaseAssets();
    } catch (\Throwable $e) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Le Post Client: Error configuring update checker: ' . $e->getMessage());
        }
    }
});

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks, execution of the plugin
 * refers to inserting all of the hooks into the WordPress execution lifecycle.
 */
function run_le_post_client_plugin()
{
    try {
        if (class_exists('LePostClient\Core\Plugin')) {
            $plugin = new LePostClient\Core\Plugin();
            $plugin->run();
        } else {
            // Handle the case where the main Plugin class is missing.
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Le Post Client: Main plugin class LePostClient\Core\Plugin not found.');
            }
        }
    } catch (\Throwable $e) {
        // Log the exception
        error_log('Le Post Client: Error during plugin initialization: ' . $e->getMessage());
        // Only display errors in admin area
        if (\is_admin() && \current_user_can('manage_options')) {
            add_action('admin_notices', function () use ($e) {
                echo '<div class="error notice"><p>';
                echo '<strong>Le Post Client Error:</strong> ' . esc_html($e->getMessage());
                echo '</p></div>';
            });
        }
    }
}

// Use the 'plugins_loaded' hook to ensure all plugins are loaded before starting ours.
add_action('plugins_loaded', 'run_le_post_client_plugin');

/**
 * Load plugin textdomain.
 */
function lepostclient_load_textdomain() {
    load_plugin_textdomain( 'lepostclient', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'init', 'lepostclient_load_textdomain' );
