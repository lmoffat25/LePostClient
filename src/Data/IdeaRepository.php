<?php

namespace LePostClient\Data;

class IdeaRepository {

    private \wpdb $db;
    private string $table_name;

    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
        $this->table_name = $this->db->prefix . 'lepostclient_post_ideas';
    }

    /**
     * Adds a new post idea to the database.
     *
     * @param array $data Associative array of idea data.
     * Expected keys: 'subject', 'description', 'user_id' (optional), 'status' (optional, defaults to 'pending').
     * @return int|false The ID of the newly inserted idea, or false on failure.
     */
    public function add_idea(array $data): int|false {
        $defaults = [
            'user_id'          => get_current_user_id() ?: null, // Can be null if not a logged-in user context
            'subject'          => '',
            'description'      => null,
            'status'           => 'pending',
            'api_theme_source' => null,
            'creation_date'    => current_time('mysql', true), // GMT time
            'last_modified_date' => current_time('mysql', true), // GMT time
        ];
        $data = wp_parse_args($data, $defaults);

        if (empty($data['subject'])) {
            return false; // Subject is mandatory
        }

        $result = $this->db->insert(
            $this->table_name,
            [
                'user_id'          => $data['user_id'] ? (int) $data['user_id'] : null,
                'subject'          => $data['subject'],
                'description'      => $data['description'],
                'status'           => $data['status'],
                'api_theme_source' => $data['api_theme_source'],
                'creation_date'    => $data['creation_date'],
                'last_modified_date' => $data['last_modified_date'],
            ],
            [
                '%d', // user_id
                '%s', // subject
                '%s', // description
                '%s', // status
                '%s', // api_theme_source
                '%s', // creation_date
                '%s', // last_modified_date
            ]
        );

        if ($result === false) {
            return false;
        }
        return $this->db->insert_id;
    }

    /**
     * Retrieves a specific post idea by its ID.
     *
     * @param int $idea_id The ID of the idea to retrieve.
     * @return object|null The idea object, or null if not found or on error.
     */
    public function get_idea(int $idea_id): ?object {
        return $this->db->get_row(
            $this->db->prepare("SELECT * FROM {$this->table_name} WHERE id = %d", $idea_id)
        );
    }

    /**
     * Retrieves ideas based on status and with pagination.
     *
     * @param string $status The status to filter by (e.g., 'pending', 'generated').
     * @param int $per_page Number of items per page.
     * @param int $page_number Current page number (1-indexed).
     * @return array Array of idea objects.
     */
    public function get_ideas_by_status(string $status, int $per_page = 20, int $page_number = 1): array {
        $offset = ($page_number - 1) * $per_page;
        $sql = $this->db->prepare(
            "SELECT * FROM {$this->table_name} WHERE status = %s ORDER BY creation_date DESC LIMIT %d OFFSET %d",
            $status,
            $per_page,
            $offset
        );
        return $this->db->get_results($sql) ?? [];
    }
    
    /**
     * Retrieves all ideas with pagination.
     *
     * @param int $per_page Number of items per page.
     * @param int $page_number Current page number (1-indexed).
     * @return array Array of idea objects.
     */
    public function get_all_ideas(int $per_page = 20, int $page_number = 1): array {
        $offset = ($page_number - 1) * $per_page;
        $sql = $this->db->prepare(
            "SELECT * FROM {$this->table_name} ORDER BY creation_date DESC LIMIT %d OFFSET %d",
            $per_page,
            $offset
        );
        return $this->db->get_results($sql) ?? [];
    }

    /**
     * Counts the total number of ideas, optionally filtered by status.
     *
     * @param string|null $status Optional status to filter by.
     * @return int The total count of ideas.
     */
    public function get_ideas_count(?string $status = null): int {
        if ($status !== null) {
            $sql = $this->db->prepare("SELECT COUNT(id) FROM {$this->table_name} WHERE status = %s", $status);
        } else {
            $sql = "SELECT COUNT(id) FROM {$this->table_name}";
        }
        return (int) $this->db->get_var($sql);
    }

    /**
     * Updates the subject and description of a post idea.
     *
     * @param int    $idea_id      The ID of the idea to update.
     * @param string $new_subject  The new subject.
     * @param string $new_description The new description.
     * @return bool True on success, false on failure.
     */
    public function update_idea_details(int $idea_id, string $new_subject, string $new_description): bool {
        if (empty($new_subject)) { // Subject should not be empty
            return false;
        }

        $result = $this->db->update(
            $this->table_name,
            [
                'subject' => $new_subject,
                'description' => $new_description,
                'last_modified_date' => current_time('mysql', true),
            ],
            ['id' => $idea_id],
            [
                '%s', // subject
                '%s', // description
                '%s', // last_modified_date
            ],
            ['%d'] // WHERE format for id
        );
        return $result !== false;
    }

    /**
     * Updates the status of a post idea and optionally its generated_post_id.
     *
     * @param int $idea_id The ID of the idea to update.
     * @param string $new_status The new status.
     * @param int|null $generated_post_id Optional ID of the generated WordPress post.
     * @return bool True on success, false on failure.
     */
    public function update_idea_status(int $idea_id, string $new_status, ?int $generated_post_id = null): bool {
        $data_to_update = [
            'status' => $new_status,
            'last_modified_date' => current_time('mysql', true)
        ];
        $formats = ['%s', '%s']; // status, last_modified_date

        if ($generated_post_id !== null) {
            $data_to_update['generated_post_id'] = $generated_post_id;
            $formats[] = '%d'; // generated_post_id
        }

        $result = $this->db->update(
            $this->table_name,
            $data_to_update,
            ['id' => $idea_id],
            $formats,
            ['%d'] // WHERE format for id
        );
        return $result !== false;
    }

    /**
     * Deletes a post idea by its ID.
     *
     * @param int $idea_id The ID of the idea to delete.
     * @return bool True on success, false on failure.
     */
    public function delete_idea(int $idea_id): bool {
        $result = $this->db->delete(
            $this->table_name,
            ['id' => $idea_id],
            ['%d']
        );
        return $result !== false;
    }
    
    // You could add a more generic update_idea method if needed:
    // public function update_idea(int $idea_id, array $data): bool { ... }
} 