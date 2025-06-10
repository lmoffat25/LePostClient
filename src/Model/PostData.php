<?php

namespace LePostClient\Model;

class PostData {
    public ?int $id; // Can be used if updating an existing post, or for API reference
    public string $title;
    public string $content;
    public string $status;    // e.g., 'draft', 'publish', 'pending'
    public ?int $author_id = null;
    public array $categories = []; // Array of category IDs or names
    public array $tags = [];       // Array of tag names
    // Add any other relevant WP_Post fields or meta data you need
    public array $meta_input = [];

    public function __construct(
        string $title,
        string $content,
        string $status = 'draft',
        ?int $id = null
    ) {
        $this->id = $id;
        $this->title = $title;
        $this->content = $content;
        $this->status = $status;
        $this->author_id = get_current_user_id(); // Default to current user
    }

    // Example: Method to prepare data for wp_insert_post
    public function toWpInsertPostArgs(): array {
        $args = [
            'post_title'   => $this->title,
            'post_content' => $this->content,
            'post_status'  => $this->status,
            'post_author'  => $this->author_id,
        ];
        if ($this->id) {
            $args['ID'] = $this->id; // For updating existing post
        }
        if (!empty($this->categories)) {
            $args['post_category'] = $this->categories;
        }
        if (!empty($this->tags)) {
            $args['tags_input'] = $this->tags;
        }
        if (!empty($this->meta_input)) {
            $args['meta_input'] = $this->meta_input;
        }
        return $args;
    }
} 