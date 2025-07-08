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

            // Check if this is an asynchronous task response
            if (isset($api_response['is_async']) && $api_response['is_async'] === true && isset($api_response['task_id'])) {
                error_log('LePostClient: Received async task response with task_id: ' . $api_response['task_id']);
                
                // Poll for task completion
                $content_data = $this->poll_task_until_complete($api_response['task_id']);
                
                if ($content_data === null) {
                    throw ContentGenerationException::failed('Failed to retrieve content from async task');
                }
                
                // Use the content data returned from the completed task
                $api_response = $content_data;
            }

            if (isset($api_response['success']) && $api_response['success'] === false) {
                $error_message = $api_response['message'] ?? 'Unknown API error occurred';
                error_log('LePostClient: API returned success=false: ' . $error_message);
                throw ContentGenerationException::failed($error_message);
            }


            // Check if the API response contains article data and extract it
            if (isset($api_response['content']['article'])) {
                $article_data = $api_response['content']['article'];
                $post_title = !empty($article_data['title']) ? sanitize_text_field($article_data['title']) : sanitize_text_field($idea->subject);
                $raw_html_content = $article_data['content'] ?? '';
                $raw_image_urls = $article_data['images'] ?? [];
            } else {
                // If the article data is not found in the expected structure, throw an exception
                error_log('LePostClient: API response missing expected article structure');
                throw ContentGenerationException::invalidResponse('Missing article data structure in API response');
            }
            
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
    public function process_images(array $raw_image_urls): array {
        $image_urls = [];
        
        foreach ($raw_image_urls as $image_url) {
            $temp_file = null;
            try {
                // Extract filename from URL, removing query parameters
                $filename = basename(parse_url($image_url, PHP_URL_PATH));
                
                // Download the image - use global WordPress function
                $temp_file = \download_url($image_url);
                
                if (\is_wp_error($temp_file)) {
                    error_log('LePostClient: Failed to download image: ' . $temp_file->get_error_message() . ' - URL: ' . $image_url);
                    continue;
                }
                
                // Get MIME type from multiple sources in order of priority
                $file_type = $this->determine_file_mime_type($temp_file, $image_url);
                $file_extension = $this->determine_file_extension($filename, $file_type);
                
                // Add enhanced logging for debugging file type issues
                error_log('LePostClient: Downloaded image - MIME type: ' . $file_type . ', Extension: ' . $file_extension . ', URL: ' . $image_url);
                
                // Ensure filename has the correct extension
                if (!empty($file_extension) && pathinfo($filename, PATHINFO_EXTENSION) !== $file_extension) {
                    $filename = pathinfo($filename, PATHINFO_FILENAME) . '.' . $file_extension;
                }
                
                // Prepare file array and upload
                $file_array = [
                    'name'     => $filename,
                    'tmp_name' => $temp_file,
                    'error'    => 0,
                    'size'     => filesize($temp_file),
                    'type'     => $file_type, // Explicitly set the MIME type
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
    
    /**
     * Determine the MIME type of a file using multiple methods
     * 
     * @param string $file_path Path to the temporary downloaded file
     * @param string $image_url Original image URL
     * @return string The determined MIME type
     */
    private function determine_file_mime_type(string $file_path, string $image_url): string {
        // 1. Try to get MIME type from the HTTP response headers (stored by WordPress)
        $response = \wp_remote_head($image_url, ['timeout' => 30]);
        if (!is_wp_error($response)) {
            $content_type = wp_remote_retrieve_header($response, 'content-type');
            if (!empty($content_type)) {
                error_log('LePostClient: Found Content-Type header: ' . $content_type . ' for ' . $image_url);
                return $content_type;
            }
        }
        
        // 2. Use PHP's fileinfo extension if available
        if (function_exists('mime_content_type')) {
            $mime_type = mime_content_type($file_path);
            if (!empty($mime_type) && $mime_type !== 'application/octet-stream') {
                return $mime_type;
            }
        }
        
        // 3. Try to get from Content-Disposition header
        if (!is_wp_error($response)) {
            $content_disposition = wp_remote_retrieve_header($response, 'content-disposition');
            if (!empty($content_disposition) && preg_match('/filename="([^"]+)"/', $content_disposition, $matches)) {
                $filename = $matches[1];
                $ext = pathinfo($filename, PATHINFO_EXTENSION);
                
                if ($ext === 'webp') {
                    return 'image/webp';
                } elseif ($ext === 'png') {
                    return 'image/png';
                } elseif ($ext === 'jpg' || $ext === 'jpeg') {
                    return 'image/jpeg';
                }
            }
        }
        
        // 4. Check debug headers that might be added by the server
        if (!is_wp_error($response)) {
            $debug_mime = wp_remote_retrieve_header($response, 'x-debug-mime');
            if (!empty($debug_mime)) {
                return $debug_mime;
            }
        }
        
        // 5. Determine from file extension in URL path (without query params)
        $path = parse_url($image_url, PHP_URL_PATH);
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        
        if ($ext === 'webp') {
            return 'image/webp';
        } elseif ($ext === 'png') {
            return 'image/png';
        } elseif ($ext === 'jpg' || $ext === 'jpeg') {
            return 'image/jpeg';
        }
        
        // 6. Fallback to generic image type
        return 'image/jpeg';
    }
    
    /**
     * Determine the appropriate file extension based on MIME type and filename
     * 
     * @param string $filename Original filename
     * @param string $mime_type MIME type of the file
     * @return string The determined file extension
     */
    private function determine_file_extension(string $filename, string $mime_type): string {
        // First check MIME type
        if ($mime_type === 'image/webp') {
            return 'webp';
        } elseif ($mime_type === 'image/png') {
            return 'png';
        } elseif ($mime_type === 'image/jpeg') {
            return 'jpg';
        }
        
        // Then check file extension from filename (without query params)
        $path = explode('?', $filename)[0];
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        
        if (in_array($ext, ['webp', 'png', 'jpg', 'jpeg'])) {
            return $ext === 'jpeg' ? 'jpg' : $ext;
        }
        
        // Default to jpg if we can't determine
        return 'jpg';
    }

    /**
     * Poll a task until it completes or times out.
     *
     * @param string $task_id The task ID to poll.
     * @param int $max_attempts Maximum number of polling attempts.
     * @param int $poll_interval Interval between polls in seconds.
     * @return array|null The completed task data or null on failure.
     */
    public function poll_task_until_complete(string $task_id, int $max_attempts = 30, int $poll_interval = 5): ?array {
        error_log("LePostClient: Starting to poll task status for task ID: {$task_id}");
        
        for ($attempt = 1; $attempt <= $max_attempts; $attempt++) {
            // Check task status
            $task_status = $this->api_client->check_task_status($task_id);
            
            if ($task_status === null) {
                error_log("LePostClient: Failed to check task status on attempt {$attempt}");
                // Wait before trying again
                sleep($poll_interval);
                continue;
            }
            
            error_log("LePostClient: Task status check #{$attempt} - Status: " . ($task_status['status'] ?? 'unknown') . 
                     ", Progress: " . ($task_status['progress'] ?? '0') . "%");
            
            // Check if task is completed
            if (isset($task_status['status']) && $task_status['status'] === 'completed') {
                error_log("LePostClient: Task completed successfully");
                
                // Check if the completed task contains content data
                if (isset($task_status['content'])) {
                    return [
                        'title' => $task_status['title'] ?? '',
                        'content' => $task_status['content'],
                        'images' => $task_status['images'] ?? []
                    ];
                }
                
                // If the task is completed but doesn't contain content directly,
                // it might be in a nested structure
                if (isset($task_status['result'])) {
                    if (is_array($task_status['result'])) {
                        if (isset($task_status['result']['content'])) {
                            return $task_status['result'];
                        } elseif (isset($task_status['result']['article']) && isset($task_status['result']['article']['content'])) {
                            return $task_status['result']['article'];
                        }
                    }
                }
                
                error_log("LePostClient: Task completed but no content found in response: " . wp_json_encode($task_status));
                return null;
            }
            
            // Check if task failed
            if (isset($task_status['status']) && $task_status['status'] === 'failed') {
                $error_message = $task_status['error'] ?? 'Unknown error';
                error_log("LePostClient: Task failed with error: {$error_message}");
                return null;
            }
            
            // Task is still in progress, wait before checking again
            sleep($poll_interval);
        }
        
        error_log("LePostClient: Exceeded maximum polling attempts ({$max_attempts}) for task ID: {$task_id}");
        return null;
    }

    /**
     * Get the settings manager instance.
     *
     * @return SettingsManager
     */
    public function get_settings_manager(): SettingsManager {
        return $this->settings_manager;
    }

    /**
     * Get the API client instance.
     *
     * @return ApiClient
     */
    public function get_api_client(): ApiClient {
        return $this->api_client;
    }

    /**
     * Get the post assembler instance.
     *
     * @return PostAssembler
     */
    public function get_post_assembler(): PostAssembler {
        return $this->post_assembler;
    }
} 