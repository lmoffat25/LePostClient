<?php

namespace LePostClient\Model;

class PostIdea {
    public ?int $id; // Optional: if you assign local IDs or get them from API for ideas themselves
    public string $subject;
    public string $description;
    public string $state; // e.g., 'pending', 'generated', 'error'

    // You can add a constructor or methods as needed
    public function __construct(
        string $subject,
        string $description,
        string $state = 'pending',
        ?int $id = null
    ) {
        $this->id = $id;
        $this->subject = $subject;
        $this->description = $description;
        $this->state = $state;
    }

    // Example getter
    public function getSubject(): string {
        return $this->subject;
    }

    // Example setter
    public function setState(string $newState): void {
        // Basic validation could go here
        $allowed_states = ['pending', 'generating', 'generated', 'error'];
        if (in_array($newState, $allowed_states)) {
            $this->state = $newState;
        }
    }
} 