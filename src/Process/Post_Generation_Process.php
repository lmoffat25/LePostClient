<?php

declare(strict_types=1);

namespace LePostClient\Process;

use LePostClient\Post\Generator;
use LePostClient\Model\PostIdea;
use LePostClient\Data\IdeaRepository;
use WP_Background_Process;

/**
 * Post Generation Background Process
 *
 * Handles the generation of posts in the background.
 *
 * @since 1.0.0
 */
class Post_Generation_Process extends WP_Background_Process {

    /**
     * @var string
     */
    protected $prefix = 'lepostclient';

    /**
     * @var string
     */
    protected $action = 'post_generation';

    /**
     * @var Generator
     */
    protected $post_generator;

    /**
     * @var IdeaRepository
     */
    protected $idea_repository;

    /**
     * Constructor
     *
     * @param Generator $post_generator The post generator instance.
     * @param IdeaRepository $idea_repository The idea repository instance.
     */
    public function __construct(Generator $post_generator, IdeaRepository $idea_repository) {
        $this->post_generator = $post_generator;
        $this->idea_repository = $idea_repository;
        parent::__construct();
    }

    /**
     * Task
     *
     * Override this method to perform any actions required on each
     * queue item. Return the modified item for further processing
     * in the next pass through. Or, return false to remove the
     * item from the queue.
     *
     * @param array $item Queue item to iterate over.
     *
     * @return mixed
     */
    protected function task($item) {
        try {
            if (!isset($item['idea_id']) || !isset($item['subject'])) {
                error_log('LePostClient: Invalid item in post generation queue - missing required fields');
                return false;
            }

            $idea_id = (int)$item['idea_id'];
            $subject = $item['subject'];
            $description = $item['description'] ?? '';

            error_log("LePostClient: Starting background generation for idea ID {$idea_id}: {$subject}");

            // Check if this idea is already being processed or completed
            $idea = $this->idea_repository->get_idea_by_id($idea_id);
            if (!$idea) {
                error_log("LePostClient: Idea ID {$idea_id} not found in database, skipping");
                return false;
            }

            if ($idea->status === 'completed') {
                error_log("LePostClient: Idea ID {$idea_id} is already completed, skipping");
                return false;
            }

            if ($idea->status === 'failed') {
                error_log("LePostClient: Idea ID {$idea_id} previously failed, skipping");
                return false;
            }

            // Check if this idea is already being processed by another instance
            $lock_key = "lepostclient_generation_lock_{$idea_id}";
            if (get_transient($lock_key)) {
                error_log("LePostClient: Idea ID {$idea_id} is already being processed by another instance, skipping");
                return false;
            }

            // Set a lock for this idea (5 minutes timeout)
            set_transient($lock_key, true, 5 * MINUTE_IN_SECONDS);

            try {
                // Double-check status before processing
                $current_idea = $this->idea_repository->get_idea_by_id($idea_id);
                if ($current_idea && $current_idea->status !== 'generating') {
                    error_log("LePostClient: Idea ID {$idea_id} status changed to {$current_idea->status}, skipping");
                    return false;
                }

                // Create post idea model
                $post_idea_model = new PostIdea($subject, $description, 'generating', $idea_id);
                
                // Generate and save the post with task_id tracking
                $result = $this->generate_with_task_tracking($post_idea_model, $idea_id);
                
                if (\is_wp_error($result)) {
                    $this->idea_repository->update_idea_status($idea_id, 'failed');
                    error_log("LePostClient: Post generation failed for idea ID {$idea_id}: " . $result->get_error_message());
                } else {
                    // Update idea status to completed with the generated post ID
                    $this->idea_repository->update_idea_status($idea_id, 'completed', $result);
                    error_log("LePostClient: Post generation completed successfully for idea ID {$idea_id}, post ID: {$result}");
                }
            } finally {
                // Always remove the lock when done
                delete_transient($lock_key);
            }
        } catch (\Throwable $e) {
            error_log("LePostClient: Exception during background post generation: " . $e->getMessage());
            if (isset($idea_id)) {
                $this->idea_repository->update_idea_status($idea_id, 'failed');
            }
            // Remove lock if it exists
            if (isset($idea_id)) {
                delete_transient("lepostclient_generation_lock_{$idea_id}");
            }
        }

        return false; // Remove the item from the queue
    }

    /**
     * Generate post with task_id tracking for real-time progress
     *
     * @param PostIdea $post_idea_model The post idea model
     * @param int $idea_id The idea ID
     * @return int|WP_Error The post ID or WP_Error on failure
     */
    private function generate_with_task_tracking(PostIdea $post_idea_model, int $idea_id) {
        try {
            // Get settings for the API call using public getter methods
            $settings_manager = $this->post_generator->get_settings_manager();
            $company_info = $settings_manager->get_company_info();
            $writing_style = $settings_manager->get_writing_style();
            
            // Make the API call to generate content
            $api_client = $this->post_generator->get_api_client();
            $api_response = $api_client->generate_content(
                $post_idea_model->subject,
                $post_idea_model->description,
                $company_info,
                $writing_style
            );
            
            if ($api_response === null) {
                throw new \Exception('Failed to fetch content from the API');
            }

            // Check if this is an asynchronous task response
            if (isset($api_response['is_async']) && $api_response['is_async'] === true && isset($api_response['task_id'])) {
                $task_id = $api_response['task_id'];
                error_log("LePostClient: Received async task response with task_id: {$task_id}");
                
                // Save the task_id to the database for client-side polling
                $this->idea_repository->update_idea_task_id($idea_id, $task_id);
                error_log("LePostClient: Saved task_id {$task_id} to database for idea ID {$idea_id}");
                
                // Poll for task completion
                $content_data = $this->post_generator->poll_task_until_complete($task_id);
                
                if ($content_data === null) {
                    throw new \Exception('Failed to retrieve content from async task');
                }
                
                // Use the content data returned from the completed task
                $api_response = $content_data;
            }

            if (isset($api_response['success']) && $api_response['success'] === false) {
                $error_message = $api_response['message'] ?? 'Unknown API error occurred';
                throw new \Exception($error_message);
            }

            // Check if the API response contains article data and extract it
            // Handle different possible response structures
            $article_data = null;
            $post_title = '';
            $raw_html_content = '';
            $raw_image_urls = [];
            
            // Try different response structures
            if (isset($api_response['content']['article'])) {
                $article_data = $api_response['content']['article'];
            } elseif (isset($api_response['result']['content']['article'])) {
                $article_data = $api_response['result']['content']['article'];
            } elseif (isset($api_response['title']) && isset($api_response['content'])) {
                // Direct response structure
                $post_title = !empty($api_response['title']) ? sanitize_text_field($api_response['title']) : sanitize_text_field($post_idea_model->subject);
                $raw_html_content = $api_response['content'] ?? '';
                $raw_image_urls = $api_response['images'] ?? [];
            } else {
                error_log("LePostClient: API response structure not recognized: " . wp_json_encode($api_response));
                throw new \Exception('Missing article data structure in API response');
            }
            
            // If we found article_data, extract the content
            if ($article_data) {
                $post_title = !empty($article_data['title']) ? sanitize_text_field($article_data['title']) : sanitize_text_field($post_idea_model->subject);
                $raw_html_content = $article_data['content'] ?? '';
                $raw_image_urls = $article_data['images'] ?? [];
            }
            
            // Log what we got from the API
            error_log('LePostClient: API returned - Title: ' . $post_title . ', Content length: ' . 
                     strlen($raw_html_content) . ', Image count: ' . count($raw_image_urls));
            
            $image_urls = $this->post_generator->process_images($raw_image_urls);

            // Use the PostAssembler to assemble the final content
            $post_assembler = $this->post_generator->get_post_assembler();
            $final_content = $post_assembler->assemble_content($raw_html_content, $image_urls);
            
            if (empty($final_content) && !empty($image_urls) && empty($raw_html_content)) {
                error_log('LePostClient: Warning - Final content was empty but had images. This might result in an empty post.');
            }

            $post_args = [
                'post_title'           => $post_title,
                'post_content'         => \wp_kses_post($final_content),
                'post_content_filtered' => $final_content,
                'post_status'          => 'draft',
                'post_author'          => \get_current_user_id(),
                'meta_input'           => [
                    '_lepostclient_generated' => true,
                ],
            ];

            error_log('LePostClient: Inserting post with title: ' . $post_title);
            $post_id = \wp_insert_post($post_args, true);

            if (\is_wp_error($post_id)) {
                error_log('LePostClient: Failed to insert post: ' . $post_id->get_error_message());
                throw new \Exception($post_id->get_error_message());
            }

            error_log('LePostClient: Successfully created post with ID: ' . $post_id);
            return $post_id;
            
        } catch (\Exception $e) {
            error_log('LePostClient: Exception during post generation: ' . $e->getMessage());
            return new \WP_Error('post_generation_failed', $e->getMessage());
        }
    }

    /**
     * Complete
     *
     * Override if applicable, but ensure that the below actions are
     * performed, or, call parent::complete().
     */
    protected function complete() {
        error_log('LePostClient: Background post generation process completed');
        parent::complete();
    }
} 