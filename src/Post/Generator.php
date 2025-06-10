<?php

namespace LePostClient\Post;

use LePostClient\Api\Client as ApiClient;
use LePostClient\Settings\Manager as SettingsManager;
use LePostClient\Post\PostAssembler;
use LePostClient\Model\PostIdea;
use LePostClient\Exceptions\ApiException;
use LePostClient\Exceptions\ContentGenerationException;
use LePostClient\Exceptions\PostGenerationException;
use WP_Error;

// Include WordPress media functions
require_once(ABSPATH . 'wp-admin/includes/file.php');
require_once(ABSPATH . 'wp-admin/includes/media.php');
require_once(ABSPATH . 'wp-admin/includes/image.php');

class Generator {

    private ApiClient $api_client;
    private SettingsManager $settings_manager;
    private PostAssembler $post_assembler;

    public function __construct(ApiClient $api_client, SettingsManager $settings_manager, PostAssembler $post_assembler) {
        $this->api_client = $api_client;
        $this->settings_manager = $settings_manager;
        $this->post_assembler = $post_assembler;
    }

    /**
     * Generates content from an idea, assembles it, and saves it as a WordPress post.
     *
     * @param PostIdea $idea The post idea object.
     * @return int|WP_Error The new post ID on success, or a WP_Error object on failure.
     */
    public function generate_and_save_post(PostIdea $idea): int|WP_Error {
        try {
            if (empty($idea->subject)) {
                throw PostGenerationException::missingField('subject');
            }

            // Log the idea we're trying to process
            error_log('LePostClient: Starting post generation for idea: ' . print_r($idea, true));

            $api_key = $this->settings_manager->get_api_key();
            if (empty($api_key)) {
                error_log('LePostClient: Missing API key. Please configure it in the settings.');
                throw PostGenerationException::missingField('API key not configured');
            }

            $company_info = $this->settings_manager->get_company_info();
            $writing_style = $this->settings_manager->get_writing_style();
            
            // Log the settings we're using
            error_log('LePostClient: Using settings - Company info: ' . (!empty($company_info) ? 'Set' : 'Empty') . 
                     ', Writing style: ' . (!empty($writing_style) ? 'Set' : 'Empty'));

            
            // Ensure they are strings, even if empty, for the API client
            $company_info = (string) $company_info;
            $writing_style = (string) $writing_style;

            $api_response = $this->api_client->generate_content(
                $idea->subject,
                $idea->description ?? '', // Ensure description is a string, even if empty
                $company_info,
                $writing_style
            );

            if ($api_response === null) {
                error_log('LePostClient: API response was null');
                throw ContentGenerationException::failed('Failed to fetch content from the API');
            }

            if (isset($api_response['success']) && $api_response['success'] === false) {
                $error_message = $api_response['message'] ?? 'Unknown API error occurred';
                error_log('LePostClient: API returned success=false: ' . $error_message);
                throw ContentGenerationException::failed($error_message);
            }

            // Ensure $api_response is an array before trying to access keys
            if (!is_array($api_response)) {
                // This can happen if perform_api_request returns null or a non-array error structure
                // that wasn't caught by the previous checks (e.g. if success key is missing altogether)
                error_log('LePostClient Post\Generator: API response was not in the expected format. Response: ' . print_r($api_response, true));
                throw ContentGenerationException::invalidResponse('generate-content');
            }

            $post_title = !empty($api_response['title']) ? \sanitize_text_field($api_response['title']) : \sanitize_text_field($idea->subject);
            $raw_html_content = $api_response['content'] ?? '';
            // Get raw image URLs from API response
            $raw_image_urls = $api_response['images'] ?? [];
            
            // Log what we got from the API
            error_log('LePostClient: API returned - Title: ' . $post_title . ', Content length: ' . 
                     strlen($raw_html_content) . ', Image count: ' . count($raw_image_urls));
            
            $image_urls = $this->process_images($raw_image_urls);

            // Here, we will use the PostAssembler.
            $final_content = $this->post_assembler->assemble_content($raw_html_content, $image_urls);
            
            if (empty($final_content) && !empty($image_urls) && empty($raw_html_content)) {
                error_log('LePostClient: Warning - Final content was empty but had images. This might result in an empty post.');
            }

            $post_args = [
                'post_title'           => $post_title, // Use the (potentially API-provided) title
                'post_content'         => \wp_kses_post($final_content), // Use wp_kses_post for security
                'post_content_filtered' => $final_content, // Store raw content in the filtered field
                'post_status'          => 'draft',
                'post_author'          => \get_current_user_id(),
                'meta_input'           => [
                    '_lepostclient_generated' => true, // Flag this as a post generated by our plugin
                ],
                // Consider adding 'post_type' if you intend to use a custom post type. Defaults to 'post'.
            ];

            error_log('LePostClient: Inserting post with title: ' . $post_title);
            $post_id = \wp_insert_post($post_args, true); // true to return WP_Error on failure

            if (\is_wp_error($post_id)) {
                error_log('LePostClient: Failed to insert post: ' . $post_id->get_error_message());
                throw PostGenerationException::wpInsertFailed($post_id->get_error_message());
            }

            error_log('LePostClient: Successfully created post with ID: ' . $post_id);
            // TODO: Optionally, update the PostIdea status (e.g., in a custom table or CPT meta)
            // TODO: Handle featured image if one of the $image_urls is meant to be the featured image

            return $post_id;
        } catch (PostGenerationException $e) {
            error_log('LePostClient Post Generation Error: ' . $e->getMessage());
            return new WP_Error('post_generation_failed', $e->getMessage());
        } catch (ContentGenerationException $e) {
            error_log('LePostClient Content Generation Error: ' . $e->getMessage());
            return new WP_Error('content_generation_failed', $e->getMessage());
        } catch (ApiException $e) {
            error_log('LePostClient API Error: ' . $e->getMessage());
            return new WP_Error('api_error', $e->getMessage());
        } catch (\Exception $e) {
            error_log('LePostClient Unexpected Error: ' . $e->getMessage());
            return new WP_Error('unexpected_error', 'An unexpected error occurred during post generation: ' . $e->getMessage());
        }
    }

    /**
     * Process and upload images to WordPress media library
     *
     * @param array $raw_image_urls Array of image URLs to process
     * @return array Array of permanent WordPress media URLs
     */
    private function process_images(array $raw_image_urls): array {
        $image_urls = [];
        
        foreach ($raw_image_urls as $image_url) {
            $temp_file = null;
            try {
                // Generate a unique filename based on the URL
                $filename = basename($image_url);
                
                // Download the image - use global WordPress function
                $temp_file = \download_url($image_url);
                
                if (\is_wp_error($temp_file)) {
                    error_log('LePostClient: Failed to download image: ' . $temp_file->get_error_message() . ' - URL: ' . $image_url);
                    continue;
                }
                
                // Add enhanced logging for debugging file type issues
                $file_type = function_exists('mime_content_type') ? mime_content_type($temp_file) : 'unknown';
                $file_extension = pathinfo($filename, PATHINFO_EXTENSION);
                error_log('LePostClient: Downloaded image - MIME type: ' . $file_type . ', Extension: ' . $file_extension . ', URL: ' . $image_url);
                
                // Prepare file array and upload
                $file_array = [
                    'name'     => $filename,
                    'tmp_name' => $temp_file,
                    'error'    => 0,
                    'size'     => filesize($temp_file),
                ];
                
                $attachment_id = \media_handle_sideload($file_array, 0);
                
                if (\is_wp_error($attachment_id)) {
                    error_log('LePostClient: Failed to upload image to media library: ' . $attachment_id->get_error_message() . ' - URL: ' . $image_url);
                    continue;
                }
                
                $permanent_url = \wp_get_attachment_url($attachment_id);
                if ($permanent_url) {
                    $image_urls[] = $permanent_url;
                    error_log('LePostClient: Successfully uploaded image to media library. URL: ' . $permanent_url);
                }
            } catch (\Exception $e) {
                error_log('LePostClient Unexpected Error: ' . $e->getMessage() . ' - URL: ' . $image_url);
                // Continue with the next image
            } finally {
                // Clean up temporary file if it exists
                if (is_string($temp_file) && file_exists($temp_file)) {
                    @unlink($temp_file);
                }
            }
        }
        
        return $image_urls;
    }
} 