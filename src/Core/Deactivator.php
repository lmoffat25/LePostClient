<?php

namespace LePostClient\Core;

class Deactivator {
    public static function deactivate() {
        // Actions to perform on plugin deactivation
        // Example: Clean up scheduled tasks or temporary data, but typically not options unless specified.
        // Remove options only if the plugin is being uninstalled, which is handled differently (e.g., uninstall.php)
        // or if there's a specific setting to remove data on deactivation.

        // Flush rewrite rules if you remove custom post types or taxonomies
        // flush_rewrite_rules();
    }
} 