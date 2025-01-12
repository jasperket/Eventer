<?php
require_once 'Database.php';

class Event {
    private $db;
    private $errors = [];

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function validate($data) {
        $this->errors = [];

        // Title validation
        if (empty($data['title'])) {
            $this->errors['title'] = 'Title is required';
        } elseif (strlen($data['title']) > 100) {
            $this->errors['title'] = 'Title must be less than 100 characters';
        }

        // Description validation
        if (empty($data['description'])) {
            $this->errors['description'] = 'Description is required';
        }

        // Event date validation
        if (empty($data['event_date'])) {
            $this->errors['event_date'] = 'Event date is required';
        } else {
            $eventDate = strtotime($data['event_date']);
            $now = time();
            if ($eventDate < $now) {
                $this->errors['event_date'] = 'Event date cannot be in the past';
            }
        }

        // Location validation
        if (empty($data['location'])) {
            $this->errors['location'] = 'Location is required';
        } elseif (strlen($data['location']) > 255) {
            $this->errors['location'] = 'Location must be less than 255 characters';
        }

        // Capacity validation
        if (empty($data['capacity'])) {
            $this->errors['capacity'] = 'Capacity is required';
        } elseif (!is_numeric($data['capacity']) || $data['capacity'] < 1) {
            $this->errors['capacity'] = 'Capacity must be a positive number';
        }

        // Status validation
        $validStatuses = ['draft', 'published'];
        if (!in_array($data['status'], $validStatuses)) {
            $this->errors['status'] = 'Invalid status';
        }

        return empty($this->errors);
    }

    public function create($data) {
        if (!$this->validate($data)) {
            return false;
        }

        try {
            $query = "INSERT INTO events (creator_id, title, description, event_date, location, capacity, status) 
                     VALUES (:creator_id, :title, :description, :event_date, :location, :capacity, :status)";
            
            $stmt = $this->db->prepare($query);
            
            $stmt->execute([
                'creator_id' => $_SESSION['user_id'], // We'll need to implement session handling
                'title' => $data['title'],
                'description' => $data['description'],
                'event_date' => $data['event_date'],
                'location' => $data['location'],
                'capacity' => $data['capacity'],
                'status' => $data['status']
            ]);

            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            $this->errors['database'] = 'Database error: ' . $e->getMessage();
            return false;
        }
    }

    public function getErrors() {
        return $this->errors;
    }
}