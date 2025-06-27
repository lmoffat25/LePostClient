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

            // Create post idea model
            $post_idea_model = new PostIdea($subject, $description, 'generating', $idea_id);
            
            // Generate and save the post
            $result = $this->post_generator->generate_and_save_post($post_idea_model);
            
            if (\is_wp_error($result)) {
                $this->idea_repository->update_idea_status($idea_id, 'failed');
                error_log("LePostClient: Post generation failed for idea ID {$idea_id}: " . $result->get_error_message());
            } else {
                // Update idea status to completed with the generated post ID
                $this->idea_repository->update_idea_status($idea_id, 'completed', $result);
                error_log("LePostClient: Post generation completed successfully for idea ID {$idea_id}, post ID: {$result}");
            }
        } catch (\Throwable $e) {
            error_log("LePostClient: Exception during background post generation: " . $e->getMessage());
            if (isset($idea_id)) {
                $this->idea_repository->update_idea_status($idea_id, 'failed');
            }
        }

        return false; // Remove the item from the queue
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