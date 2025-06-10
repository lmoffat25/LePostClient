<?php

namespace LePostClient\Admin\Controller;

use LePostClient\Api\Client as ApiClient;
use LePostClient\Post\Generator as PostGenerator;
use LePostClient\Settings\Manager as SettingsManager;
use LePostClient\Model\PostIdea;
use LePostClient\Data\IdeaRepository;

class IdeasListController {

    private ApiClient $api_client;
    private PostGenerator $post_generator;
    private SettingsManager $settings_manager;
    private IdeaRepository $idea_repository;

    public function __construct(
        ApiClient $api_client, 
        PostGenerator $post_generator, 
        SettingsManager $settings_manager, 
        IdeaRepository $idea_repository
    ) {
        $this->api_client = $api_client;
        $this->post_generator = $post_generator;
        $this->settings_manager = $settings_manager;
        $this->idea_repository = $idea_repository;
    }

    public function render_page() {
        settings_errors('lepostclient_notices');
        
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $items_per_page = 20;

        // Fetch ideas that are not yet generated (pending, generating, failed)
        $pending_ideas = $this->idea_repository->get_ideas_by_status('pending', $items_per_page, $current_page);
        $generating_ideas = $this->idea_repository->get_ideas_by_status('generating', $items_per_page, $current_page);
        $failed_ideas = $this->idea_repository->get_ideas_by_status('failed', $items_per_page, $current_page);
        
        // Merge and sort them. This is a simplified approach.
        // For robust pagination with multiple statuses, a dedicated repository method would be better.
        $ideas = array_merge($pending_ideas, $generating_ideas, $failed_ideas);
        // Sort by creation_date descending as an example (can be more sophisticated)
        usort($ideas, function($a, $b) {
            return strtotime($b->creation_date) - strtotime($a->creation_date);
        });
        
        // For pagination, we need the total count of these specific statuses.
        $total_pending = $this->idea_repository->get_ideas_count('pending');
        $total_generating = $this->idea_repository->get_ideas_count('generating');
        $total_failed = $this->idea_repository->get_ideas_count('failed');
        $total_ideas = $total_pending + $total_generating + $total_failed;
        
        $total_pages = ceil($total_ideas / $items_per_page);

        // If we merged results from different paginated queries, the current $ideas array might be too large
        // or not correctly paginated. This simplistic merge is not ideal for accurate pagination across multiple statuses.
        // A proper solution would be a new repository method: get_ideas_by_statuses(['pending', 'generating', 'failed'], $per_page, $page_num)
        // For now, we'll slice the merged array to fit the $items_per_page for the current view, acknowledging this isn't perfect for pagination logic.
        if ($total_ideas > $items_per_page && count($ideas) > $items_per_page) {
             // This is a temporary fix for display, proper pagination needs a better query
            $ideas = array_slice($ideas, 0, $items_per_page);
        }

        $view_path = dirname(__FILE__, 2) . '/View/ideas-list-page.php';
        if (file_exists($view_path)) {
            include $view_path;
        } else {
            echo '<div class="error"><p>Ideas List view file not found.</p></div>';
        }
    }

    /**
     * Handles the admin-post action to add a new idea manually.
     */
    public function handle_add_idea_manually() {
        if (!isset($_POST['lepc_add_idea_manually_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['lepc_add_idea_manually_nonce'])), 'lepc_add_idea_manually_action')) {
            wp_die(__( 'Security check failed. Please try again.', 'lepostclient' ), 'Nonce Verification Failed', ['response' => 403]);
        }

        if (!current_user_can('manage_options')) { 
            wp_die(__( 'You do not have sufficient permissions to perform this action.', 'lepostclient' ), 'Permission Denied', ['response' => 403]);
        }

        $subject = isset($_POST['lepc_idea_subject_manual']) ? sanitize_text_field(wp_unslash($_POST['lepc_idea_subject_manual'])) : '';
        $description = isset($_POST['lepc_idea_description_manual']) ? sanitize_textarea_field(wp_unslash($_POST['lepc_idea_description_manual'])) : '';

        if (empty($subject)) {
            add_settings_error(
                'lepostclient_notices',
                'idea_missing_subject',
                __('Idea subject cannot be empty.', 'lepostclient'),
                'error'
            );
        } else {
            $result = $this->idea_repository->add_idea([
                'subject' => $subject,
                'description' => $description,
            ]);

            if ($result === false) {
                add_settings_error(
                    'lepostclient_notices',
                    'idea_add_failed',
                    __('Failed to save the new idea. Please try again.', 'lepostclient'),
                    'error'
                );
            } else {
                add_settings_error(
                    'lepostclient_notices',
                    'idea_added_successfully',
                    __('New idea added successfully.', 'lepostclient'),
                    'updated' 
                );
            }
        }
        
        $redirect_url = admin_url('admin.php?page=lepostclient_ideas_list');
        wp_safe_redirect($redirect_url);
        exit;
    }

    /**
     * Handles the admin-post action to update an existing idea.
     */
    public function handle_update_idea() {
        if (!isset($_POST['lepostclient_update_idea_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['lepostclient_update_idea_nonce'])), 'lepostclient_update_idea_action')) {
            wp_die(__( 'Security check failed for update. Please try again.', 'lepostclient' ), 'Nonce Verification Failed', ['response' => 403]);
        }

        if (!current_user_can('manage_options')) { // Or a more specific capability
            wp_die(__( 'You do not have sufficient permissions to perform this action.', 'lepostclient' ), 'Permission Denied', ['response' => 403]);
        }

        $idea_id = isset($_POST['idea_id']) ? (int) $_POST['idea_id'] : 0;
        $subject = isset($_POST['idea_subject']) ? sanitize_text_field(wp_unslash($_POST['idea_subject'])) : '';
        $description = isset($_POST['idea_description']) ? sanitize_textarea_field(wp_unslash($_POST['idea_description'])) : '';

        if (empty($idea_id) || empty($subject)) {
            add_settings_error(
                'lepostclient_notices',
                'idea_update_missing_data',
                __('Idea ID or subject cannot be empty for update.', 'lepostclient'),
                'error'
            );
        } else {
            $result = $this->idea_repository->update_idea_details($idea_id, $subject, $description);

            if ($result === false) {
                add_settings_error(
                    'lepostclient_notices',
                    'idea_update_failed',
                    __('Failed to update the idea. Please try again.', 'lepostclient'),
                    'error'
                );
            } else {
                add_settings_error(
                    'lepostclient_notices',
                    'idea_updated_successfully',
                    __('Idea updated successfully.', 'lepostclient'),
                    'updated'
                );
            }
        }
        
        $redirect_url = admin_url('admin.php?page=lepostclient_ideas_list');
        wp_safe_redirect($redirect_url);
        exit;
    }

    /**
     * Handles the admin-post action to initiate post generation.
     * Schedules a WP Cron job and redirects back with a notice.
     */
    public function handle_initiate_generate_post() {
        if (!isset($_POST['lepostclient_initiate_generate_post_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['lepostclient_initiate_generate_post_nonce'])), 'lepostclient_initiate_generate_post_action')) {
            wp_die(__( 'Security check failed. Please try again.', 'lepostclient' ), 'Nonce Verification Failed', ['response' => 403]);
        }

        $idea_id = isset($_POST['idea_id']) ? sanitize_text_field($_POST['idea_id']) : null;
        $idea_subject = isset($_POST['idea_subject']) ? sanitize_text_field($_POST['idea_subject']) : null;
        $idea_description = isset($_POST['idea_description']) ? sanitize_textarea_field($_POST['idea_description']) : '';

        if (empty($idea_subject) || $idea_id === null) { 
            add_settings_error(
                'lepostclient_notices',
                'missing_idea_data_for_generation',
                __('Could not initiate post generation: Missing idea ID or subject when trying to generate.', 'lepostclient'),
                'error'
            );
            $redirect_url = admin_url('admin.php?page=lepostclient_ideas_list');
            wp_safe_redirect($redirect_url);
            exit;
        }
        
        $updated_to_generating = $this->idea_repository->update_idea_status((int)$idea_id, 'generating');
        
        if(!$updated_to_generating){
            error_log("LePostClient: Failed to update idea ID {$idea_id} to 'generating' status before scheduling cron.");
        }

        $cron_args = [
            'idea_id' => (int)$idea_id, 
            'subject' => $idea_subject,
            'description' => $idea_description,
        ];

        $event_scheduled = wp_schedule_single_event(time(), 'lepostclient_process_idea_generation_event', [$cron_args]);
        
        if ($event_scheduled === false) {
            error_log("LePostClient CRON_ERROR: wp_schedule_single_event() returned false for idea ID {$idea_id}. The cron event was not scheduled.");
            $this->idea_repository->update_idea_status((int)$idea_id, 'failed', null, 'Cron scheduling failed.');
            add_settings_error(
                'lepostclient_notices',
                'generation_schedule_failed',
                __('Error: Could not schedule the background generation task. The post generation cannot proceed. This might be due to a server configuration issue with WP-Cron. Please try again or contact support.', 'lepostclient'),
                'error'
            );
        } else {
            add_settings_error(
                'lepostclient_notices',
                'generation_initiated',
                sprintf(
                    __('Post generation for idea "%s" (ID: %s) has been initiated. It will be processed in the background.', 'lepostclient'),
                    esc_html($idea_subject),
                    esc_html($idea_id)
                ),
                'updated'
            );
        }
        
        $redirect_url = admin_url('admin.php?page=lepostclient_ideas_list');
        wp_safe_redirect($redirect_url);
        exit;
    }

    /**
     * Handles the admin-post action to delete a post idea.
     */
    public function handle_delete_idea() {
        if (!isset($_POST['lepostclient_delete_idea_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['lepostclient_delete_idea_nonce'])), 'lepostclient_delete_idea_action')) {
            wp_die(__( 'Security check failed for delete. Please try again.', 'lepostclient' ), 'Nonce Verification Failed', ['response' => 403]);
        }

        if (!current_user_can('manage_options')) { // Or a more specific capability for deleting
            wp_die(__( 'You do not have sufficient permissions to perform this action.', 'lepostclient' ), 'Permission Denied', ['response' => 403]);
        }

        $idea_id = isset($_POST['idea_id']) ? (int) $_POST['idea_id'] : 0;

        if (empty($idea_id)) {
            add_settings_error(
                'lepostclient_notices',
                'idea_delete_missing_id',
                __('Idea ID cannot be empty for deletion.', 'lepostclient'),
                'error'
            );
        } else {
            $result = $this->idea_repository->delete_idea($idea_id);

            if ($result === false) {
                add_settings_error(
                    'lepostclient_notices',
                    'idea_delete_failed',
                    __('Failed to delete the idea. Please try again.', 'lepostclient'),
                    'error'
                );
            } else {
                add_settings_error(
                    'lepostclient_notices',
                    'idea_deleted_successfully',
                    __('Idea deleted successfully.', 'lepostclient'),
                    'updated'
                );
            }
        }
        
        $redirect_url = admin_url('admin.php?page=lepostclient_ideas_list');
        wp_safe_redirect($redirect_url);
        exit;
    }

    /**
     * Handles the admin-post action to generate ideas using AI.
     */
    public function handle_generate_ideas_ai() {
        if (!isset($_POST['lepc_generate_ideas_ai_nonce_field']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['lepc_generate_ideas_ai_nonce_field'])), 'lepc_generate_ideas_ai_action')) {
            wp_die(__( 'Security check failed for AI idea generation. Please try again.', 'lepostclient' ), 'Nonce Verification Failed', ['response' => 403]);
        }

        if (!current_user_can('manage_options')) {
            wp_die(__( 'You do not have sufficient permissions to perform this action.', 'lepostclient' ), 'Permission Denied', ['response' => 403]);
        }

        $ai_subject = isset($_POST['lepc_ai_subject']) ? sanitize_text_field(wp_unslash($_POST['lepc_ai_subject'])) : '';
        $num_ideas = isset($_POST['lepc_ai_num_ideas']) ? intval($_POST['lepc_ai_num_ideas']) : 1;

        if (empty($ai_subject)) {
            add_settings_error(
                'lepostclient_notices',
                'ai_ideas_missing_subject',
                __('Base subject/keyword cannot be empty for AI idea generation.', 'lepostclient'),
                'error'
            );
        } elseif ($num_ideas <= 0 || $num_ideas > 10) { // Assuming API max limit is 10, consistent with provided API controller logic
            add_settings_error(
                'lepostclient_notices',
                'ai_ideas_invalid_count',
                __('Number of ideas must be between 1 and 10.', 'lepostclient'),
                'error'
            );
        } else {
            $api_response = $this->api_client->generate_ai_ideas($ai_subject, $num_ideas);

            if (is_wp_error($api_response)) {
                add_settings_error(
                    'lepostclient_notices',
                    'ai_ideas_api_error',
                    sprintf(
                        __('Failed to generate AI ideas from API: %s', 'lepostclient'),
                        $api_response->get_error_message()
                    ),
                    'error'
                );
            } elseif (isset($api_response['ideas']) && is_array($api_response['ideas'])) {
                $ideas_from_api = $api_response['ideas'];
                $free_usage_info = $api_response['free_usage_info'] ?? null;
                $saved_count = 0;

                foreach ($ideas_from_api as $idea_subject_string) {
                    // The new API returns an array of idea strings.
                    if (is_string($idea_subject_string) && !empty($idea_subject_string)) {
                        $this->idea_repository->add_idea([
                            'subject' => $idea_subject_string,
                            'description' => '', // No description from this endpoint
                            'api_theme_source' => $ai_subject, // Store the original theme used for generation
                        ]);
                        $saved_count++;
                    }
                }

                $success_message = sprintf(
                    _n(
                        'Successfully generated and saved %d idea.', 
                        'Successfully generated and saved %d ideas.', 
                        $saved_count, 
                        'lepostclient'
                    ),
                    $saved_count
                );

                if ($free_usage_info) {
                    $success_message .= ' ' . __('API Usage:', 'lepostclient');
                    if (isset($free_usage_info['used_free_quota'])) {
                        $success_message .= ' ' . sprintf(__('Used free quota: %s.', 'lepostclient'), $free_usage_info['used_free_quota'] ? __('Yes', 'lepostclient') : __('No', 'lepostclient'));
                    }
                    if (isset($free_usage_info['credits_charged'])) {
                        $success_message .= ' ' . sprintf(__('Credits charged: %d.', 'lepostclient'), $free_usage_info['credits_charged']);
                    }
                    if (isset($free_usage_info['free_remaining_this_month'])) {
                        $success_message .= ' ' . sprintf(__('Free ideas remaining this month: %d/%d.', 'lepostclient'), $free_usage_info['free_remaining_this_month'], $free_usage_info['free_total_per_month'] ?? 'N/A');
                    }
                }

                add_settings_error(
                    'lepostclient_notices',
                    'ai_ideas_generated_successfully',
                    $success_message,
                    'updated'
                );

            } else {
                add_settings_error(
                    'lepostclient_notices',
                    'ai_ideas_api_unexpected_response',
                    __('Received an unexpected response from the AI idea generation API.', 'lepostclient'),
                    'error'
                );
            }
        }
        
        wp_safe_redirect(admin_url('admin.php?page=lepostclient_ideas_list'));
        exit;
    }

    /**
     * Handles the admin-post action to import ideas from a CSV file.
     */
    public function handle_import_csv() {
        if (!isset($_POST['lepostclient_import_csv_nonce_field']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['lepostclient_import_csv_nonce_field'])), 'lepostclient_import_csv_action')) {
            wp_die(__( 'Security check failed for CSV import. Please try again.', 'lepostclient' ), 'Nonce Verification Failed', ['response' => 403]);
        }

        if (!current_user_can('manage_options')) {
            wp_die(__( 'You do not have sufficient permissions to perform this action.', 'lepostclient' ), 'Permission Denied', ['response' => 403]);
        }

        if (!isset($_FILES['lepc_csv_file']) || empty($_FILES['lepc_csv_file']['tmp_name'])) {
            add_settings_error('lepostclient_notices', 'csv_import_no_file', __('No CSV file was uploaded. Please choose a file to import.', 'lepostclient'), 'error');
            wp_safe_redirect(admin_url('admin.php?page=lepostclient_ideas_list'));
            exit;
        }

        $file = $_FILES['lepc_csv_file'];

        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $upload_errors = [
                UPLOAD_ERR_INI_SIZE   => __('The uploaded file exceeds the upload_max_filesize directive in php.ini.', 'lepostclient'),
                UPLOAD_ERR_FORM_SIZE  => __('The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.', 'lepostclient'),
                UPLOAD_ERR_PARTIAL    => __('The uploaded file was only partially uploaded.', 'lepostclient'),
                UPLOAD_ERR_NO_FILE    => __('No file was uploaded.', 'lepostclient'), // Should be caught above, but good to have
                UPLOAD_ERR_NO_TMP_DIR => __('Missing a temporary folder.', 'lepostclient'),
                UPLOAD_ERR_CANT_WRITE => __('Failed to write file to disk.', 'lepostclient'),
                UPLOAD_ERR_EXTENSION  => __('A PHP extension stopped the file upload.', 'lepostclient'),
            ];
            $error_message = $upload_errors[$file['error']] ?? __('Unknown upload error.', 'lepostclient');
            add_settings_error('lepostclient_notices', 'csv_import_upload_error', $error_message, 'error');
            wp_safe_redirect(admin_url('admin.php?page=lepostclient_ideas_list'));
            exit;
        }

        // Validate file type (MIME type and extension)
        $file_type = mime_content_type($file['tmp_name']);
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if ($file_type !== 'text/csv' && $file_type !== 'application/csv' && $file_extension !== 'csv') {
             // Some systems might report CSV as 'text/plain' if they don't have a specific CSV mime type registered.
             // We can be a bit more lenient here or stricter based on requirements.
             // For now, also checking 'text/plain' if extension is csv.
            if (!($file_type === 'text/plain' && $file_extension === 'csv')) {
                add_settings_error('lepostclient_notices', 'csv_import_invalid_type', __('Invalid file type. Please upload a valid CSV file.', 'lepostclient'), 'error');
                wp_safe_redirect(admin_url('admin.php?page=lepostclient_ideas_list'));
                exit;
            }
        }

        $imported_count = 0;
        $failed_count = 0;
        $row_number = 0;

        if (($handle = fopen($file['tmp_name'], 'r')) !== false) {
            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                $row_number++;
                if (count($data) >= 1) { // Ensure there's at least one column for the subject
                    $subject = sanitize_text_field($data[0]);
                    $description = isset($data[1]) ? sanitize_textarea_field($data[1]) : ''; // Description is optional

                    if (!empty($subject)) {
                        $result = $this->idea_repository->add_idea([
                            'subject'     => $subject,
                            'description' => $description,
                        ]);
                        if ($result) {
                            $imported_count++;
                        } else {
                            $failed_count++;
                            error_log("LePostClient CSV Import: Failed to save row {$row_number} - Subject: {$subject}");
                        }
                    } else {
                        $failed_count++; // Subject was empty
                        error_log("LePostClient CSV Import: Skipped row {$row_number} due to empty subject.");
                    }
                } else {
                    $failed_count++; // Row did not have enough columns
                     error_log("LePostClient CSV Import: Skipped row {$row_number} due to insufficient columns.");
                }
            }
            fclose($handle);

            if ($imported_count > 0) {
                $message = sprintf(
                    _n(
                        'Successfully imported %d idea from CSV.', 
                        'Successfully imported %d ideas from CSV.', 
                        $imported_count, 
                        'lepostclient'
                    ),
                    $imported_count
                );
                if ($failed_count > 0) {
                    $message .= ' ' . sprintf(__('%d rows could not be imported.', 'lepostclient'), $failed_count);
                }
                add_settings_error('lepostclient_notices', 'csv_import_success', $message, 'updated');
            } elseif ($failed_count > 0) {
                 add_settings_error('lepostclient_notices', 'csv_import_all_failed', sprintf(__('CSV import completed, but %d rows could not be imported. Please check the file format and content.', 'lepostclient'), $failed_count), 'warning');
            } else {
                 add_settings_error('lepostclient_notices', 'csv_import_empty', __('The CSV file was empty or contained no valid data to import.', 'lepostclient'), 'warning');
            }

        } else {
            add_settings_error('lepostclient_notices', 'csv_import_file_error', __('Could not open the uploaded CSV file for processing.', 'lepostclient'), 'error');
        }

        wp_safe_redirect(admin_url('admin.php?page=lepostclient_ideas_list'));
        exit;
    }

    /**
     * Handles bulk actions for post ideas (Generate Selected, Delete Selected).
     */
    public function handle_bulk_actions() {
        // error_log('[BULK ACTIONS DEBUG] handle_bulk_actions() reached.'); // DIAGNOSTIC REMOVED
        // error_log('[BULK ACTIONS DEBUG] _POST data: ' . print_r($_POST, true)); // DIAGNOSTIC REMOVED
        // // wp_die('[BULK ACTIONS DEBUG] handle_bulk_actions() was called. Check PHP error log. POST data logged.'); // DIAGNOSTIC REMOVED

        if (!isset($_POST['lepostclient_bulk_actions_nonce_field']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['lepostclient_bulk_actions_nonce_field'])), 'lepostclient_bulk_actions_nonce_action')) {
            // error_log('[BULK ACTIONS DEBUG] Nonce verification failed.'); // DIAGNOSTIC REMOVED
            wp_die(__( 'Security check failed for bulk actions. Please try again.', 'lepostclient' ), 'Nonce Verification Failed', ['response' => 403]);
        }

        if (!current_user_can('manage_options')) {
            // error_log('[BULK ACTIONS DEBUG] Permission check failed.'); // DIAGNOSTIC REMOVED
            wp_die(__( 'You do not have sufficient permissions to perform this action.', 'lepostclient' ), 'Permission Denied', ['response' => 403]);
        }

        $action = sanitize_text_field($_POST['bulk_action_top'] ?? (''.sanitize_text_field($_POST['bulk_action_bottom'] ?? '').''));
        if ($action === '-1' || $action === '') { 
             $action = sanitize_text_field($_POST['bulk_action_bottom'] ?? '-1');
        }
        // error_log('[BULK ACTIONS DEBUG] Action selected: ' . $action); // DIAGNOSTIC REMOVED
        
        $idea_ids = isset($_POST['post_idea_ids']) ? array_map('intval', (array)$_POST['post_idea_ids']) : [];
        // error_log('[BULK ACTIONS DEBUG] Idea IDs selected: ' . print_r($idea_ids, true)); // DIAGNOSTIC REMOVED

        if (empty($idea_ids)) {
            add_settings_error('lepostclient_notices', 'bulk_action_no_ideas', __('No ideas selected for the bulk action.', 'lepostclient'), 'warning');
            wp_safe_redirect(admin_url('admin.php?page=lepostclient_ideas_list'));
            exit;
        }

        $processed_count = 0;
        $error_count = 0;

        switch ($action) {
            case 'bulk_generate':
                foreach ($idea_ids as $idea_id) {
                    $idea = $this->idea_repository->get_idea($idea_id);
                    if ($idea && in_array($idea->status, ['pending', 'failed'])) {
                        $this->idea_repository->update_idea_status($idea_id, 'generating');
                        wp_schedule_single_event(time(), 'lepostclient_process_idea_generation_event', [[
                            'idea_id' => $idea_id,
                            'subject' => $idea->subject,
                            'description' => $idea->description,
                        ]]);
                        $processed_count++;
                    } else {
                        $error_count++;
                    }
                }
                if ($processed_count > 0) {
                    add_settings_error('lepostclient_notices', 'bulk_generate_success', sprintf(__('Scheduled generation for %d ideas.', 'lepostclient'), $processed_count), 'updated');
                }
                if ($error_count > 0) {
                    add_settings_error('lepostclient_notices', 'bulk_generate_error', sprintf(__('%d ideas could not be scheduled (either not found, already generated, or currently generating).', 'lepostclient'), $error_count), 'warning');
                }
                break;

            case 'bulk_delete':
                foreach ($idea_ids as $idea_id) {
                    if ($this->idea_repository->delete_idea($idea_id)) {
                        $processed_count++;
                    } else {
                        $error_count++;
                    }
                }
                if ($processed_count > 0) {
                    add_settings_error('lepostclient_notices', 'bulk_delete_success', sprintf(__('Successfully deleted %d ideas.', 'lepostclient'), $processed_count), 'updated');
                }
                if ($error_count > 0) {
                    add_settings_error('lepostclient_notices', 'bulk_delete_error', sprintf(__('Could not delete %d ideas.', 'lepostclient'), $error_count), 'warning');
                }
                break;
            
            default:
                add_settings_error('lepostclient_notices', 'bulk_action_invalid', __('No valid bulk action selected.', 'lepostclient'), 'warning');
                break;
        }

        wp_safe_redirect(admin_url('admin.php?page=lepostclient_ideas_list'));
        exit;
    }

    // Placeholder for AJAX handler, as anticipated in Plugin.php (commented out section)
    /*
    public function handle_generate_post_ajax() {
        check_ajax_referer('lepostclient_generate_post_nonce', 'nonce');

        // Get idea_id from $_POST
        $idea_id = isset($_POST['idea_id']) ? intval($_POST['idea_id']) : 0;

        if (!$idea_id) {
            wp_send_json_error(['message' => __('Invalid Idea ID.', 'lepostclient')]);
            return;
        }

        // Fetch idea details (perhaps from API or a local cache/DB if you store them)
        // $idea_data = $this->api_client->getPostIdeaDetails($idea_id);
        // if (!$idea_data) {
        //     wp_send_json_error(['message' => __('Could not fetch idea details.', 'lepostclient')]);
        //     return;
        // }

        // $result = $this->post_generator->generatePostFromIdea($idea_data);

        // if ($result && !is_wp_error($result)) {
        //     wp_send_json_success(['message' => __('Post generated successfully!', 'lepostclient'), 'post_id' => $result]);
        // } else {
        //     wp_send_json_error(['message' => is_wp_error($result) ? $result->get_error_message() : __('Failed to generate post.', 'lepostclient')]);
        // }
        wp_die(); // this is required to terminate immediately and return a proper response
    }
    */
} 