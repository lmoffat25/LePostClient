<?php

namespace LePostClient\Core;

// Import necessary classes
use LePostClient\Admin\AdminMenu;
use LePostClient\Admin\Controller\DashboardController;
use LePostClient\Admin\Controller\IdeasListController;
use LePostClient\Admin\Controller\SettingsController;
use LePostClient\Admin\Controller\ApiKeySetupController;
use LePostClient\Api\Client as ApiClient;
use LePostClient\Settings\Manager as SettingsManager;
use LePostClient\Post\Generator as PostGenerator;
use LePostClient\Post\PostAssembler;
use LePostClient\Model\PostIdea;
use LePostClient\Data\IdeaRepository;
use LePostClient\Exceptions\ApiException;
use LePostClient\Exceptions\ContentGenerationException;
use LePostClient\Exceptions\PostGenerationException;
use LePostClient\Process\Post_Generation_Process;
use Puc_v5p6_Factory;

class Plugin {

    protected ApiClient $api_client;
    protected SettingsManager $settings_manager;
    protected PostGenerator $post_generator;
    protected IdeaRepository $idea_repository;
    protected AdminMenu $admin_menu;
    protected IdeasListController $ideas_list_controller;
    protected SettingsController $settings_controller;
    protected ApiKeySetupController $api_key_setup_controller;
    protected Post_Generation_Process $post_generation_process;

    public function __construct() {
        // Initialize core components that don't have external dependencies first
        $this->settings_manager = new SettingsManager();
        $post_assembler = new PostAssembler();
        $this->idea_repository = new IdeaRepository();

        // Initialize components that might depend on settings or other basic services
        $this->api_client = new ApiClient($this->settings_manager);

        // Initialize components that depend on other initialized components
        $this->post_generator = new PostGenerator($this->api_client, $this->settings_manager, $post_assembler);
        
        // Initialize the background process
        $this->post_generation_process = new Post_Generation_Process($this->post_generator, $this->idea_repository);

        // Initialize admin-specific components
        $dashboard_controller = new DashboardController($this->api_client, $this->settings_manager);
        $this->ideas_list_controller = new IdeasListController(
            $this->api_client, 
            $this->post_generator, 
            $this->settings_manager, 
            $this->idea_repository,
            $this->post_generation_process // Pass the background process to the controller
        );
        $this->settings_controller = new SettingsController($this->settings_manager, $this->api_client);
        $this->api_key_setup_controller = new ApiKeySetupController($this->settings_manager);

        $this->admin_menu = new AdminMenu(
            $dashboard_controller,
            $this->ideas_list_controller,
            $this->settings_controller
        );

        $this->initialize_updater();
        $this->setup_actions();
        $this->setup_filters();
    }

    public function run() {
        $this->load_hooks();
    }

    private function load_hooks() {
        add_action('admin_menu', [$this->admin_menu, 'register_menus']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);

        // Register the admin-post action for saving settings
        add_action('admin_post_lepostclient_save_settings', [$this->settings_controller, 'handle_save_settings']);

        // Register the admin-post action for saving API key from setup screen
        add_action('admin_post_lepostclient_save_api_key_setup', [$this->api_key_setup_controller, 'handle_save_api_key']);

        // Register the admin-post action for initiating post generation
        add_action('admin_post_lepostclient_initiate_generate_post', [$this->ideas_list_controller, 'handle_initiate_generate_post']);

        // Register the admin-post action for adding an idea manually
        add_action('admin_post_lepostclient_add_idea_manually', [$this->ideas_list_controller, 'handle_add_idea_manually']);

        // Register the admin-post action for updating an idea
        add_action('admin_post_lepostclient_update_idea', [$this->ideas_list_controller, 'handle_update_idea']);

        // Register the admin-post action for deleting an idea
        add_action('admin_post_lepostclient_delete_idea', [$this->ideas_list_controller, 'handle_delete_idea']);

        // Register the admin-post action for generating ideas with AI
        add_action('admin_post_lepostclient_generate_ideas_ai', [$this->ideas_list_controller, 'handle_generate_ideas_ai']);

        // Register the admin-post action for importing ideas from CSV
        add_action('admin_post_lepostclient_import_csv', [$this->ideas_list_controller, 'handle_import_csv']);

        // Register the admin-post action for bulk actions
        add_action('admin_post_lepostclient_bulk_actions', [$this->ideas_list_controller, 'handle_bulk_actions']);

        // Register AJAX endpoint for checking generation status
        add_action('wp_ajax_lepostclient_check_generation_status', [$this->ideas_list_controller, 'handle_check_generation_status']);

        // Add hook to check if API key is set and show setup screen if not
        add_action('admin_init', [$this, 'check_api_key']);
    }

    /**
     * Check if API key is set and show setup screen if not
     * 
     * @since 1.0.3
     * 
     * @return void
     */
    public function check_api_key(): void {
        // Skip for AJAX requests
        if (wp_doing_ajax()) {
            return;
        }

        // Skip for non-admin pages
        if (!is_admin()) {
            return;
        }
        
        // Check if API key is set
        if (!$this->settings_manager->is_configured() && $this->should_show_api_key_setup()) {
            // Use admin_notices with priority 0 to ensure it runs first
            add_action('admin_notices', [$this, 'render_api_key_setup'], 0);
            add_action('admin_head', [$this, 'add_api_key_setup_styles']);
        }
    }

    /**
     * Render API key setup screen
     * 
     * @since 1.0.3
     * 
     * @return void
     */
    public function render_api_key_setup(): void {
        // Output the overlay div here with proper styling
        ?>
        <div class="lepc-api-key-setup-overlay" style="
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 999999;
            background-color: rgba(0, 0, 0, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
        ">
            <?php $this->api_key_setup_controller->render_page(); ?>
        </div>
        <?php
    }

    /**
     * Determine if we should show the API key setup screen on the current page
     *
     * @since 1.0.4
     *
     * @return bool True if we should show the API key setup screen
     */
    private function should_show_api_key_setup(): bool {
        global $pagenow;
        
        // Don't show on these pages
        $excluded_pages = [
            'plugins.php',
            'update.php',
            'update-core.php',
            'admin-ajax.php',
            'admin-post.php',
            'async-upload.php',
            'media-upload.php',
            'customize.php'
        ];
        
        if (in_array($pagenow, $excluded_pages)) {
            return false;
        }
        
        // Don't show on plugin/theme installation pages
        if ($pagenow === 'plugin-install.php' || $pagenow === 'theme-install.php') {
            return false;
        }
        
        return true;
    }

    /**
     * Add styles to hide admin content when showing API key setup screen
     * 
     * @since 1.0.3
     * 
     * @return void
     */
    public function add_api_key_setup_styles(): void {
        ?>
        <style>
            /* Ensure our overlay is visible and on top */
            .lepc-api-key-setup-overlay {
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                right: 0 !important;
                bottom: 0 !important;
                z-index: 999999 !important;
                background-color: rgba(0, 0, 0, 0.7) !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
            }
            
            /* Style the container */
            .lepc-api-key-setup-container {
                background-color: #fff;
                border-radius: 5px;
                box-shadow: 0 0 20px rgba(0, 0, 0, 0.2);
                width: 100%;
                max-width: 500px;
                padding: 30px;
                text-align: center;
            }
        </style>
        <?php
    }

    private function initialize_updater() {
        // Ensure the class exists before using it.
        if ( ! class_exists( 'Puc_v5p6_Factory' ) ) {
            return;
        }

        // Use GitHub for updates - the plugin will check for new releases tagged on GitHub
        // and prompt users to update when a new version is available
        $updateChecker = Puc_v5p6_Factory::buildUpdateChecker(
            'https://github.com/leonmoffat/LePostClient/',
            dirname( __FILE__, 3 ) . '/lepostclient.php',
            'lepostclient'
        );

        // Set to use releases instead of the source code from a branch
        // This will look for releases/tags in your GitHub repository
        $updateChecker->getVcsApi()->enableReleaseAssets();
        
        // Optional: If using a private repository, uncomment these lines and define
        // the LEPOSTCLIENT_GITHUB_TOKEN constant in wp-config.php
        // if ( defined('LEPOSTCLIENT_GITHUB_TOKEN') ) {
        //    $updateChecker->setAuthentication(LEPOSTCLIENT_GITHUB_TOKEN);
        // }
        
        // Tell the updater to prefer tagged releases over branch commits
        $updateChecker->getVcsApi()->setReleaseAssetPreferred(true);
    }

    /**
     * Helper method to mark an idea as failed.
     *
     * @param int $idea_id The idea ID to mark as failed
     * @param string $reason The reason for the failure
     */

    /**
     * Helper method to mark an idea as failed.
     *
     * @param int $idea_id The idea ID to mark as failed
     * @param string $reason The reason for the failure
     */
    private function mark_idea_as_failed($idea_id, $reason) {
        try {
            $idea_repository = new IdeaRepository();
            $idea_repository->update_idea_status((int)$idea_id, 'failed');
            
            // If the repository has a method to update notes, use it
            if (method_exists($idea_repository, 'update_idea_notes')) {
                $idea_repository->update_idea_notes((int)$idea_id, $reason);
            }
        } catch (\Exception $inner_e) {
            error_log('LePostClient Error: Failed to update idea status: ' . $inner_e->getMessage());
        }
    }

    public function enqueue_admin_assets($hook) {
        // Only enqueue on our plugin's pages
        if (strpos($hook, 'lepostclient') === false) {
            return;
        }
        
        // Enqueue CSS
        wp_enqueue_style(
            'lepostclient-admin-style',
            LEPOSTCLIENT_URL . 'assets/css/admin-style.css',
            [],
            LEPOSTCLIENT_VERSION
        );
        
        // Enqueue JS
        wp_enqueue_script(
            'lepostclient-admin-script',
            LEPOSTCLIENT_URL . 'assets/js/admin-script.js',
            ['jquery'],
            LEPOSTCLIENT_VERSION,
            true
        );
        
        // Pass data to the script
        wp_localize_script(
            'lepostclient-admin-script',
            'lepostclient_data',
            [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'status_nonce' => wp_create_nonce('lepostclient_status_check_nonce'),
            ]
        );
    }

    /**
     * Sets up WordPress actions used by the plugin.
     *
     * @since 1.0.0
     * @return void
     */
    private function setup_actions() {
        // Nothing to register here
    }

    /**
     * Sets up WordPress filters used by the plugin.
     *
     * @since 1.0.0
     * @return void
     */
    private function setup_filters() {
        // Use post_content_filtered for generated posts in frontend
        add_filter('the_content', [$this, 'use_filtered_content'], 1);
        
        // Disable editor normalization for our generated posts
        add_filter('use_block_editor_for_post', [$this, 'maybe_disable_block_editor'], 10, 2);
        add_filter('wp_editor_settings', [$this, 'preserve_editor_content'], 10, 2);
        
        // Preserve HTML in the block editor
        add_filter('block_editor_preload_settings', [$this, 'preserve_block_editor_html'], 10, 2);
        
        // Filter pre-save content to preserve raw HTML
        add_filter('wp_insert_post_data', [$this, 'preserve_post_content_on_save'], 10, 2);

        // Add support for additional file types
        add_filter('upload_mimes', [$this, 'allow_additional_file_types']);
        
        // Fix MIME type detection for certain file types
        add_filter('wp_check_filetype_and_ext', [$this, 'fix_mime_type_detection'], 10, 5);
    }

    /**
     * Uses post_content_filtered instead of post_content for generated posts
     * This prevents WordPress from altering HTML structure with wpautop
     *
     * @since 1.0.0
     * @param string $content The post content
     * @return string The filtered content
     */
    public function use_filtered_content($content) {
        // Only proceed if we're in the main query and displaying a single post
        if (!is_singular() || !in_the_loop() || !is_main_query()) {
            return $content;
        }
        
        $post_id = get_the_ID();
        
        // If this is a post generated by our plugin
        if (get_post_meta($post_id, '_lepostclient_generated', true)) {
            global $post;
            
            // If post_content_filtered is not empty, use it instead
            if (!empty($post->post_content_filtered)) {
                // Remove wpautop to prevent it from processing our filtered content
                remove_filter('the_content', 'wpautop');
                return $post->post_content_filtered;
            }
        }
        
        return $content;
    }

    /**
     * Optionally disables the block editor for generated posts
     * 
     * @since 1.0.0
     * @param bool $use_block_editor Whether to use block editor
     * @param WP_Post $post The post being edited
     * @return bool Whether to use block editor
     */
    public function maybe_disable_block_editor($use_block_editor, $post) {
        // If this is a post generated by our plugin
        if (get_post_meta($post->ID, '_lepostclient_generated', true)) {
            // Uncomment the next line to force classic editor for generated posts
            // return false;
        }
        
        return $use_block_editor;
    }

    /**
     * Preserves content formatting in the Classic editor.
     *
     * @since 1.0.0
     * @param array  $settings   Editor settings.
     * @param string $editor_id  Editor ID (e.g. 'content', 'excerpt').
     * @return array Modified settings.
     */
    public function preserve_editor_content( array $settings, string $editor_id ): array {
        // Determine the post being edited.
        $post_id = isset( $_GET['post'] )        ? absint( $_GET['post'] ) :
                   ( isset( $_POST['post_ID'] )  ? absint( $_POST['post_ID'] ) :
                   ( isset( $GLOBALS['post']->ID ) ? absint( $GLOBALS['post']->ID ) : 0 ) );

        if ( ! $post_id ) {
            return $settings; // Nothing to do.
        }

        // Only tweak settings for posts generated by LePostClient.
        if ( get_post_meta( $post_id, '_lepostclient_generated', true ) ) {
            // Disable automatic paragraphs.
            $settings['wpautop'] = false;

            // Preserve raw HTML/line-breaks in TinyMCE.
            $settings['tinymce'] = array_merge(
                $settings['tinymce'] ?? [],
                [
                    'entity_encoding' => 'raw',
                    'verify_html'     => false,
                    'cleanup'         => false,
                ]
            );
        }

        return $settings;
    }

    /**
     * Preserves HTML content in the block editor preload settings
     * 
     * @since 1.0.0
     * @param array $settings Editor settings
     * @param WP_Post $post Post being edited
     * @return array Modified settings
     */
    public function preserve_block_editor_html($settings, $post) {
        // If this is a post generated by our plugin
        if (get_post_meta($post->ID, '_lepostclient_generated', true)) {
            // If we have settings for editor
            if (isset($settings['editor'])) {
                // Disable features that might normalize content
                $settings['editor']['__experimentalFeatures'] = array_merge(
                    isset($settings['editor']['__experimentalFeatures']) ? $settings['editor']['__experimentalFeatures'] : array(),
                    array('typography' => array('formatTypes' => false))
                );
            }
        }
        
        return $settings;
    }

    /**
     * Preserves the raw HTML content when saving posts
     * 
     * @since 1.0.0
     * @param array $data The slashed post data
     * @param array $postarr The raw post data
     * @return array Modified post data
     */
    public function preserve_post_content_on_save($data, $postarr) {
        // Only apply to our generated posts
        if (isset($postarr['ID']) && get_post_meta($postarr['ID'], '_lepostclient_generated', true)) {
            // Store the filtered content again if it exists
            if (!empty($postarr['post_content_filtered'])) {
                $data['post_content_filtered'] = $postarr['post_content_filtered'];
            } 
            // If post_content_filtered is empty but we previously had it, preserve the original
            else {
                $original_post = get_post($postarr['ID']);
                if ($original_post && !empty($original_post->post_content_filtered)) {
                    $data['post_content_filtered'] = $original_post->post_content_filtered;
                }
            }
            
            // Also consider restoring from post_content_filtered to post_content
            // if the content has been altered by editor filters
            if (!empty($data['post_content_filtered'])) {
                // Check if we should restore original paragraphs
                if (strpos($data['post_content_filtered'], '<p>') !== false && 
                    strpos($data['post_content'], '<p>') === false) {
                    $data['post_content'] = $data['post_content_filtered'];
                }
            }
        }
        
        return $data;
    }

    /**
     * Allow additional file types in WordPress media uploads
     * 
     * @since 1.0.0
     * @param array $mimes Current allowed MIME types
     * @return array Modified MIME types
     */
    public function allow_additional_file_types($mimes) {
        // Add image formats that might be causing the issue
        $mimes['png'] = 'image/png';
        $mimes['webp'] = 'image/webp';
        $mimes['avif'] = 'image/avif';
        
        // Ideogram.ai uses PNG images but they might have query parameters
        // This ensures WordPress recognizes them correctly
        if (!isset($mimes['png'])) {
            $mimes['png'] = 'image/png';
        }
        
        return $mimes;
    }
    
    /**
     * Fix the MIME type detection for certain file types
     * 
     * @since 1.0.0
     * @param array $data File data
     * @param string $file Full path to the file
     * @param string $filename The name of the file
     * @param array $mimes Allowed mime types
     * @param string $real_mime Real MIME type of the file
     * @return array Modified file data
     */
    public function fix_mime_type_detection($data, $file, $filename, $mimes, $real_mime) {
        // If WordPress already has a valid file type, don't override it
        if (!empty($data['ext']) && !empty($data['type']) && $data['type'] !== 'application/octet-stream') {
            return $data;
        }
        
        // Clean filename to handle URLs that contain query parameters
        $clean_filename = preg_replace('/\?.*$/', '', $filename);
        $wp_file_type = wp_check_filetype($clean_filename, $mimes);
        
        // Enhanced logging for debugging
        error_log('LePostClient: MIME detection - Filename: ' . $filename . ', Clean filename: ' . $clean_filename . 
                  ', Real MIME: ' . $real_mime . ', WP filetype: ' . print_r($wp_file_type, true));
        
        // Handle WebP files
        if ('webp' === $wp_file_type['ext'] || (strpos($clean_filename, '.webp') !== false)) {
            $data['ext'] = 'webp';
            $data['type'] = 'image/webp';
            error_log('LePostClient: Fixed MIME type detection for WebP file: ' . $filename);
        }
        
        // Handle PNG files
        else if ('png' === $wp_file_type['ext'] || (strpos($clean_filename, '.png') !== false)) {
            $data['ext'] = 'png';
            $data['type'] = 'image/png';
            error_log('LePostClient: Fixed MIME type detection for PNG file: ' . $filename);
        }
        
        // Handle JPG files
        else if ('jpg' === $wp_file_type['ext'] || 'jpeg' === $wp_file_type['ext'] || 
                (strpos($clean_filename, '.jpg') !== false) || (strpos($clean_filename, '.jpeg') !== false)) {
            $data['ext'] = 'jpg';
            $data['type'] = 'image/jpeg';
            error_log('LePostClient: Fixed MIME type detection for JPEG file: ' . $filename);
        }
        
        // Check actual file content for image type if still undetected
        if (empty($data['type']) || $data['type'] === 'application/octet-stream') {
            // Check for WebP signature
            $handle = fopen($file, 'rb');
            if ($handle) {
                $header = fread($handle, 12);
                fclose($handle);
                
                // Check WebP signature - "RIFF" + 4 bytes + "WEBP"
                if (substr($header, 0, 4) === 'RIFF' && substr($header, 8, 4) === 'WEBP') {
                    $data['ext'] = 'webp';
                    $data['type'] = 'image/webp';
                    error_log('LePostClient: Identified WebP by file signature: ' . $filename);
                }
            }
        }
        
        // Additional check for ideogram.ai images
        if (strpos($filename, 'ideogram.ai') !== false && empty($data['ext'])) {
            $data['ext'] = 'png';
            $data['type'] = 'image/png';
            error_log('LePostClient: Forced PNG type for ideogram.ai image: ' . $filename);
        }
        
        // If we have a real MIME but no detected type, use the real MIME as a fallback
        if ((empty($data['type']) || $data['type'] === 'application/octet-stream') && 
            !empty($real_mime) && $real_mime !== 'application/octet-stream') {
            // Map common MIME types to extensions
            $mime_to_ext = [
                'image/webp' => 'webp',
                'image/png' => 'png',
                'image/jpeg' => 'jpg',
                'image/jpg' => 'jpg',
                'image/gif' => 'gif'
            ];
            
            if (isset($mime_to_ext[$real_mime])) {
                $data['ext'] = $mime_to_ext[$real_mime];
                $data['type'] = $real_mime;
                error_log('LePostClient: Used real MIME type as fallback: ' . $real_mime);
            }
        }
        
        return $data;
    }

    /**
     * Helper method to check if a specific admin-post action is being performed
     * This is a safer alternative to wp_doing_action() which may not be available in all WP versions
     *
     * @since 1.0.4
     * @param string $action The action name to check for
     * @return bool True if the specified admin-post action is being performed
     */
    private function is_admin_post_action(string $action): bool {
        // Check if we're on admin-post.php
        $is_admin_post = (strpos($_SERVER['PHP_SELF'] ?? '', 'admin-post.php') !== false);
        
        // Check if the specified action is being performed
        $current_action = $_POST['action'] ?? $_GET['action'] ?? '';
        $is_target_action = ($current_action === $action);
        
        return $is_admin_post && $is_target_action;
    }
} 