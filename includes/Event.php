<?php
require_once 'Database.php';

class Event
{
    private $db;
    private $errors = [];

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getUpcomingEvents()
    {
        try {
            $query = "SELECT e.*, u.username as creator_name,
                     (SELECT COUNT(*) FROM registrations WHERE event_id = e.id AND status != 'cancelled') as registered_count
                     FROM events e
                     JOIN users u ON e.creator_id = u.id
                     WHERE e.event_date > NOW()
                     AND e.status = 'published'
                     ORDER BY e.event_date ASC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $this->errors['database'] = 'Error fetching events: ' . $e->getMessage();
            return false;
        }
    }

    public function getEventById($eventId)
    {
        try {
            $query = "SELECT e.*, u.username as creator_name,
                     (SELECT COUNT(*) FROM registrations WHERE event_id = e.id AND status != 'cancelled') as registered_count
                     FROM events e
                     JOIN users u ON e.creator_id = u.id
                     WHERE e.id = ?";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$eventId]);
            
            return $stmt->fetch();
        } catch (PDOException $e) {
            $this->errors['database'] = 'Error fetching event: ' . $e->getMessage();
            return false;
        }
    }

    public function getUserRegistrationStatus($eventId, $userId)
    {
        try {
            $query = "SELECT * FROM registrations WHERE event_id = ? AND user_id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$eventId, $userId]);
            
            return $stmt->fetch();
        } catch (PDOException $e) {
            $this->errors['database'] = 'Error checking registration status: ' . $e->getMessage();
            return false;
        }
    }

    public function validate($data)
    {
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

    public function create($data)
    {
        if (!$this->validate($data)) {
            return false;
        }

        try {
            $this->db->beginTransaction();

            $query = "INSERT INTO events (creator_id, title, description, event_date, location, capacity, status) 
                     VALUES (:creator_id, :title, :description, :event_date, :location, :capacity, :status)";

            $stmt = $this->db->prepare($query);
            
            $stmt->execute([
                'creator_id' => $_SESSION['user_id'],
                'title' => $data['title'],
                'description' => $data['description'],
                'event_date' => $data['event_date'],
                'location' => $data['location'],
                'capacity' => $data['capacity'],
                'status' => $data['status']
            ]);

            $eventId = $this->db->lastInsertId();

            // Register the creator automatically
            if (!$this->registerUser($eventId, $_SESSION['user_id'], 'confirmed')) {
                $this->db->rollBack();
                return false;
            }

            $this->db->commit();
            return $eventId;

        } catch (PDOException $e) {
            $this->db->rollBack();
            $this->errors['database'] = 'Database error: ' . $e->getMessage();
            return false;
        }
    }

    public function registerUser($eventId, $userId, $status = 'pending')
    {
        try {
            // Check if user is already registered
            $checkQuery = "SELECT COUNT(*) FROM registrations WHERE user_id = ? AND event_id = ?";
            $checkStmt = $this->db->prepare($checkQuery);
            $checkStmt->execute([$userId, $eventId]);
            
            if ($checkStmt->fetchColumn() > 0) {
                $this->errors['registration'] = 'User is already registered for this event';
                return false;
            }

            // Check event capacity
            $capacityQuery = "SELECT e.capacity, COUNT(r.id) as current_registrations 
                            FROM events e 
                            LEFT JOIN registrations r ON e.id = r.event_id 
                            AND r.status != 'cancelled'
                            WHERE e.id = ? 
                            GROUP BY e.id, e.capacity";
            
            $capacityStmt = $this->db->prepare($capacityQuery);
            $capacityStmt->execute([$eventId]);
            $capacityInfo = $capacityStmt->fetch();

            if ($capacityInfo && $capacityInfo['current_registrations'] >= $capacityInfo['capacity']) {
                // If capacity is full and waitlist is requested, set status to waitlisted
                if (isset($_POST['waitlist'])) {
                    $status = 'waitlisted';
                } else {
                    $this->errors['registration'] = 'Event has reached maximum capacity';
                    return false;
                }
            }

            // Create the registration
            $registrationQuery = "INSERT INTO registrations (user_id, event_id, status) 
                                VALUES (?, ?, ?)";
            
            $registrationStmt = $this->db->prepare($registrationQuery);
            $registrationStmt->execute([$userId, $eventId, $status]);

            return true;

        } catch (PDOException $e) {
            $this->errors['registration'] = 'Registration failed: ' . $e->getMessage();
            return false;
        }
    }

    public function getErrors()
    {
        return $this->errors;
    }
}