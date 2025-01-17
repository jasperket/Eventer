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

    /**
     * Get all upcoming published events
     */
    public function getUpcomingEvents()
    {
        try {
            $query = "SELECT e.*, u.username as creator_name,
                     COUNT(CASE WHEN r.status != 'cancelled' THEN 1 END) as registered_count
                     FROM events e
                     JOIN users u ON e.creator_id = u.id
                     LEFT JOIN registrations r ON e.id = r.event_id
                     WHERE e.event_date > NOW()
                     AND e.status = 'published'
                     GROUP BY e.id, e.creator_id, e.title, e.description, e.event_date, 
                              e.location, e.capacity, e.status, e.created_at, e.updated_at,
                              u.username
                     ORDER BY e.event_date ASC";

            $stmt = $this->db->prepare($query);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $this->errors['database'] = 'Error fetching events: ' . $e->getMessage();
            return false;
        }
    }

    /**
     * Get all events hosted by a specific user
     */
    public function getHostedEvents($userId)
    {
        try {
            $query = "SELECT e.*, u.username as creator_name,
                     COUNT(CASE WHEN r.status != 'cancelled' THEN 1 END) as registered_count
                     FROM events e
                     JOIN users u ON e.creator_id = u.id
                     LEFT JOIN registrations r ON e.id = r.event_id
                     WHERE e.creator_id = ?
                     GROUP BY e.id, e.creator_id, e.title, e.description, e.event_date, 
                              e.location, e.capacity, e.status, e.created_at, e.updated_at,
                              u.username
                     ORDER BY 
                        CASE 
                            WHEN e.status = 'published' AND e.event_date > NOW() THEN 1
                            WHEN e.status = 'draft' THEN 2
                            ELSE 3
                        END,
                        e.event_date DESC";

            $stmt = $this->db->prepare($query);
            $stmt->execute([$userId]);

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $this->errors['database'] = 'Error fetching hosted events: ' . $e->getMessage();
            return false;
        }
    }


    /**
     * Get all events a user has registered for
     */
    public function getRegisteredEvents($userId)
    {
        try {
            $query = "SELECT e.*, u.username as creator_name,
                     r.status as registration_status,
                     COUNT(DISTINCT r2.id) as registered_count
                     FROM events e
                     JOIN users u ON e.creator_id = u.id
                     JOIN registrations r ON e.id = r.event_id AND r.user_id = ?
                     LEFT JOIN registrations r2 ON e.id = r2.event_id AND r2.status != 'cancelled'
                     GROUP BY e.id, e.creator_id, e.title, e.description, e.event_date, 
                              e.location, e.capacity, e.status, e.created_at, e.updated_at,
                              u.username, r.status
                     ORDER BY 
                        CASE 
                            WHEN e.event_date > NOW() AND r.status IN ('confirmed', 'pending', 'waitlisted') THEN 1
                            WHEN e.event_date > NOW() AND r.status = 'cancelled' THEN 2
                            ELSE 3
                        END,
                        e.event_date DESC";

            $stmt = $this->db->prepare($query);
            $stmt->execute([$userId]);

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $this->errors['database'] = 'Error fetching registered events: ' . $e->getMessage();
            return false;
        }
    }

    /**
     * Get a specific event by ID with registration count
     */
    public function getEventById($eventId)
    {
        try {
            $query = "SELECT e.*, u.username as creator_name,
                     COUNT(CASE WHEN r.status != 'cancelled' THEN 1 END) as registered_count
                     FROM events e
                     JOIN users u ON e.creator_id = u.id
                     LEFT JOIN registrations r ON e.id = r.event_id
                     WHERE e.id = ?
                     GROUP BY e.id, e.creator_id, e.title, e.description, e.event_date,
                              e.location, e.capacity, e.status, e.created_at, e.updated_at,
                              u.username";

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

    public function delete($eventId, $userId)
    {
        try {
            $this->db->beginTransaction();

            // Check if user is the event creator
            $event = $this->getEventById($eventId);
            if (!$event || $event['creator_id'] !== $userId) {
                $this->errors['permission'] = 'You do not have permission to delete this event';
                $this->db->rollBack();
                return false;
            }

            // Delete registrations first
            $deleteRegistrationsQuery = "DELETE FROM registrations WHERE event_id = ?";
            $deleteRegistrationsStmt = $this->db->prepare($deleteRegistrationsQuery);
            $deleteRegistrationsStmt->execute([$eventId]);

            // Then delete the event
            $deleteEventQuery = "DELETE FROM events WHERE id = ? AND creator_id = ?";
            $deleteEventStmt = $this->db->prepare($deleteEventQuery);
            $deleteEventStmt->execute([$eventId, $userId]);

            if ($deleteEventStmt->rowCount() > 0) {
                $this->db->commit();
                return true;
            } else {
                $this->db->rollBack();
                $this->errors['database'] = 'Failed to delete event';
                return false;
            }
        } catch (PDOException $e) {
            $this->db->rollBack();
            $this->errors['database'] = 'Database error: ' . $e->getMessage();
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
        $validStatuses = ['draft', 'published', 'cancelled'];
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

    public function update($data)
    {
        if (!$this->validate($data)) {
            return false;
        }

        try {
            $this->db->beginTransaction();

            // Check if the event exists and belongs to the current user
            $event = $this->getEventById($data['id']);
            if (!$event || $event['creator_id'] !== $_SESSION['user_id']) {
                $this->errors['database'] = 'You do not have permission to edit this event';
                $this->db->rollBack();
                return false;
            }

            // Check capacity against current registrations
            $registeredCount = $event['registered_count'];
            if ($data['capacity'] < $registeredCount) {
                $this->errors['capacity'] = 'New capacity cannot be less than current registrations';
                $this->db->rollBack();
                return false;
            }

            $query = "UPDATE events 
                     SET title = :title, 
                         description = :description,
                         event_date = :event_date,
                         location = :location,
                         capacity = :capacity,
                         status = :status
                     WHERE id = :id AND creator_id = :creator_id";

            $stmt = $this->db->prepare($query);

            $result = $stmt->execute([
                'id' => $data['id'],
                'creator_id' => $_SESSION['user_id'],
                'title' => $data['title'],
                'description' => $data['description'],
                'event_date' => $data['event_date'],
                'location' => $data['location'],
                'capacity' => $data['capacity'],
                'status' => $data['status']
            ]);

            // If event is cancelled, cancel all pending registrations
            if ($data['status'] === 'cancelled') {
                $cancelQuery = "UPDATE registrations 
                              SET status = 'cancelled' 
                              WHERE event_id = ? AND status != 'cancelled'";
                $cancelStmt = $this->db->prepare($cancelQuery);
                $cancelStmt->execute([$data['id']]);
            }

            // Check if capacity was increased
            if ($data['capacity'] > $event['capacity']) {
                // Move waitlisted registrations to confirmed if there's new capacity
                $newSpots = $data['capacity'] - $registeredCount;
                if ($newSpots > 0) {
                    $updateWaitlistQuery = "UPDATE registrations 
                                          SET status = 'confirmed' 
                                          WHERE event_id = ? 
                                          AND status = 'waitlisted' 
                                          ORDER BY registered_at ASC 
                                          LIMIT ?";
                    $waitlistStmt = $this->db->prepare($updateWaitlistQuery);
                    $waitlistStmt->execute([$data['id'], $newSpots]);
                }
            }

            $this->db->commit();
            return true;
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

    public function getEventRegistrations($userId)
    {
        try {
            $query = "SELECT r.*, u.username, e.title as event_title, e.event_date,
                     (SELECT COUNT(*) FROM registrations WHERE event_id = e.id AND status != 'cancelled') as registered_count,
                     e.capacity
                     FROM registrations r
                     JOIN events e ON r.event_id = e.id
                     JOIN users u ON r.user_id = u.id
                     WHERE e.creator_id = ?
                     AND r.status NOT IN ('confirmed', 'cancelled')
                     ORDER BY e.event_date ASC, r.registered_at ASC";

            $stmt = $this->db->prepare($query);
            $stmt->execute([$userId]);

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $this->errors['database'] = 'Error fetching registrations: ' . $e->getMessage();
            return false;
        }
    }

    public function updateRegistrationStatus($registrationId, $newStatus, $userId)
    {
        try {
            // Verify that the user is the event creator
            $query = "SELECT e.creator_id, e.capacity, r.event_id,
                     (SELECT COUNT(*) FROM registrations 
                      WHERE event_id = r.event_id AND status = 'confirmed') as confirmed_count
                     FROM registrations r
                     JOIN events e ON r.event_id = e.id
                     WHERE r.id = ?";

            $stmt = $this->db->prepare($query);
            $stmt->execute([$registrationId]);
            $registration = $stmt->fetch();

            if (!$registration || $registration['creator_id'] !== $userId) {
                $this->errors['permission'] = 'You do not have permission to update this registration';
                return false;
            }

            // If changing to confirmed, check capacity
            if (
                $newStatus === 'confirmed' &&
                $registration['confirmed_count'] >= $registration['capacity']
            ) {
                $this->errors['capacity'] = 'Event has reached maximum capacity';
                return false;
            }

            $query = "UPDATE registrations SET status = ? WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$newStatus, $registrationId]);

            return true;
        } catch (PDOException $e) {
            $this->errors['database'] = 'Error updating registration: ' . $e->getMessage();
            return false;
        }
    }

    public function getErrors()
    {
        return $this->errors;
    }
}
