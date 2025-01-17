<?php
require_once 'Database.php';
require_once 'Logger.php';
require_once 'logger-utils.php';

/**
 * Event class handles all event-related operations including CRUD operations
 * and registration management
 */
class Event
{
    private $db;
    private $errors = [];

    /**
     * Initialize database connection
     */
    public function __construct()
    {
        try {
            $this->db = Database::getInstance()->getConnection();
            logger()->debug('Event class initialized');
        } catch (PDOException $e) {
            logException($e, 'Failed to initialize Event class');
            throw $e;
        }
    }

    /**
     * Get upcoming published events with registration counts
     */
    public function getUpcomingEvents()
    {
        try {
            logger()->debug('Fetching upcoming events');

            // Get published events with registration count, joined with creator info
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
            $events = $stmt->fetchAll();

            logger()->info('Successfully fetched upcoming events', [
                'count' => count($events)
            ]);

            return $events;
        } catch (PDOException $e) {
            logDatabaseError($e->errorInfo, $query);
            $this->errors['database'] = 'Error fetching events: ' . $e->getMessage();
            return false;
        }
    }

    /**
     * Get events created by a specific user
     * Sorted by status and date (active published events first)
     */
    public function getHostedEvents($userId)
    {
        try {
            logger()->debug('Fetching hosted events', ['userId' => $userId]);

            // Get events with registration count, filtered by creator
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
            $events = $stmt->fetchAll();

            logger()->info('Successfully fetched hosted events', [
                'userId' => $userId,
                'count' => count($events)
            ]);

            return $events;
        } catch (PDOException $e) {
            logDatabaseError($e->errorInfo, $query, [$userId]);
            $this->errors['database'] = 'Error fetching hosted events: ' . $e->getMessage();
            return false;
        }
    }

    /**
     * Get events that a user has registered for
     * Includes registration status and sorted by upcoming active registrations first
     */
    public function getRegisteredEvents($userId)
    {
        try {
            logger()->debug('Fetching registered events', ['userId' => $userId]);

            // Get events with registration status and count
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
            $events = $stmt->fetchAll();

            logger()->info('Successfully fetched registered events', [
                'userId' => $userId,
                'count' => count($events)
            ]);

            return $events;
        } catch (PDOException $e) {
            logDatabaseError($e->errorInfo, $query, [$userId]);
            $this->errors['database'] = 'Error fetching registered events: ' . $e->getMessage();
            return false;
        }
    }

    /**
     * Get detailed event information by ID
     * Includes creator info and registration count
     */
    public function getEventById($eventId)
    {
        try {
            logger()->debug('Fetching event details', ['eventId' => $eventId]);

            // Get event with creator info and registration count
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
            $event = $stmt->fetch();

            if ($event) {
                logger()->info('Successfully fetched event details', [
                    'eventId' => $eventId,
                    'title' => $event['title'],
                    'status' => $event['status']
                ]);
            } else {
                logger()->warning('Event not found', ['eventId' => $eventId]);
            }

            return $event;
        } catch (PDOException $e) {
            logDatabaseError($e->errorInfo, $query, [$eventId]);
            $this->errors['database'] = 'Error fetching event: ' . $e->getMessage();
            return false;
        }
    }

    /**
     * Create a new event and automatically register the creator
     */
    public function create($data)
    {
        try {
            logger()->info('Attempting to create event', [
                'title' => $data['title'],
                'creator_id' => $_SESSION['user_id']
            ]);

            // Validate event data before creation
            if (!$this->validate($data)) {
                logger()->warning('Event validation failed', [
                    'errors' => $this->errors
                ]);
                return false;
            }

            $this->db->beginTransaction();

            // Insert event record
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
                logger()->error('Failed to register creator for event', [
                    'eventId' => $eventId,
                    'userId' => $_SESSION['user_id']
                ]);
                $this->db->rollBack();
                return false;
            }

            $this->db->commit();

            logger()->info('Event created successfully', [
                'eventId' => $eventId,
                'title' => $data['title']
            ]);

            return $eventId;
        } catch (PDOException $e) {
            $this->db->rollBack();
            logDatabaseError($e->errorInfo, $query, $data);
            $this->errors['database'] = 'Database error: ' . $e->getMessage();
            return false;
        }
    }

    /**
     * Update event details and handle status/capacity changes
     * Updates waitlist if capacity increases
     */
    public function update($data)
    {
        try {
            logger()->info('Attempting to update event', [
                'eventId' => $data['id'],
                'title' => $data['title']
            ]);

            if (!$this->validate($data)) {
                logger()->warning('Event update validation failed', [
                    'eventId' => $data['id'],
                    'errors' => $this->errors
                ]);
                return false;
            }

            $this->db->beginTransaction();

            // Check if the event exists and belongs to the current user
            $event = $this->getEventById($data['id']);
            if (!$event || $event['creator_id'] !== $_SESSION['user_id']) {
                logger()->warning('Unauthorized event update attempt', [
                    'eventId' => $data['id'],
                    'userId' => $_SESSION['user_id']
                ]);
                $this->errors['database'] = 'You do not have permission to edit this event';
                $this->db->rollBack();
                return false;
            }

            // Check capacity against current registrations
            if ($data['capacity'] < $event['registered_count']) {
                logger()->warning('Invalid capacity update', [
                    'eventId' => $data['id'],
                    'newCapacity' => $data['capacity'],
                    'currentRegistrations' => $event['registered_count']
                ]);
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
            $stmt->execute([
                'id' => $data['id'],
                'creator_id' => $_SESSION['user_id'],
                'title' => $data['title'],
                'description' => $data['description'],
                'event_date' => $data['event_date'],
                'location' => $data['location'],
                'capacity' => $data['capacity'],
                'status' => $data['status']
            ]);

            // Handle status changes and capacity updates
            if ($data['status'] === 'cancelled') {
                logger()->info('Cancelling all registrations for event', [
                    'eventId' => $data['id']
                ]);

                $cancelQuery = "UPDATE registrations 
                              SET status = 'cancelled' 
                              WHERE event_id = ? AND status != 'cancelled'";
                $cancelStmt = $this->db->prepare($cancelQuery);
                $cancelStmt->execute([$data['id']]);
            } elseif ($data['capacity'] > $event['capacity']) {
                $newSpots = $data['capacity'] - $event['registered_count'];

                logger()->info('Processing waitlist due to capacity increase', [
                    'eventId' => $data['id'],
                    'newSpots' => $newSpots
                ]);

                if ($newSpots > 0) {
                    $updateWaitlistQuery = "UPDATE registrations 
                                          SET status = 'confirmed' 
                                          WHERE event_id = ? 
                                          AND status = 'waitlisted' 
                                          ORDER BY registered_at ASC 
                                          LIMIT ?";
                    $waitlistStmt = $this->db->prepare($updateWaitlistQuery);
                    $waitlistStmt->execute([$data['id'], $newSpots]);

                    $confirmedCount = $waitlistStmt->rowCount();
                    if ($confirmedCount > 0) {
                        logger()->info('Confirmed waitlisted registrations', [
                            'eventId' => $data['id'],
                            'count' => $confirmedCount
                        ]);
                    }
                }
            }

            $this->db->commit();

            logger()->info('Event updated successfully', [
                'eventId' => $data['id'],
                'title' => $data['title'],
                'status' => $data['status']
            ]);

            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            logDatabaseError($e->errorInfo, $query, $data);
            $this->errors['database'] = 'Database error: ' . $e->getMessage();
            return false;
        }
    }

    /**
     * Delete an event and all its registrations
     * Verifies event ownership before deletion
     */
    public function delete($eventId, $userId)
    {
        try {
            logger()->info('Attempting to delete event', [
                'eventId' => $eventId,
                'userId' => $userId
            ]);

            $this->db->beginTransaction();

            // Check if user is the event creator
            $event = $this->getEventById($eventId);
            if (!$event || $event['creator_id'] !== $userId) {
                logger()->warning('Unauthorized event deletion attempt', [
                    'eventId' => $eventId,
                    'userId' => $userId
                ]);
                $this->errors['permission'] = 'You do not have permission to delete this event';
                $this->db->rollBack();
                return false;
            }

            // Delete registrations first
            $deleteRegistrationsQuery = "DELETE FROM registrations WHERE event_id = ?";
            $deleteRegistrationsStmt = $this->db->prepare($deleteRegistrationsQuery);
            $deleteRegistrationsStmt->execute([$eventId]);
            $deletedRegistrations = $deleteRegistrationsStmt->rowCount();

            logger()->info('Deleted event registrations', [
                'eventId' => $eventId,
                'count' => $deletedRegistrations
            ]);

            // Then delete the event
            $deleteEventQuery = "DELETE FROM events WHERE id = ? AND creator_id = ?";
            $deleteEventStmt = $this->db->prepare($deleteEventQuery);
            $deleteEventStmt->execute([$eventId, $userId]);

            if ($deleteEventStmt->rowCount() > 0) {
                $this->db->commit();
                logger()->info('Event deleted successfully', [
                    'eventId' => $eventId,
                    'title' => $event['title']
                ]);
                return true;
            } else {
                logger()->error('Failed to delete event', [
                    'eventId' => $eventId
                ]);
                $this->db->rollBack();
                $this->errors['database'] = 'Failed to delete event';
                return false;
            }
        } catch (PDOException $e) {
            $this->db->rollBack();
            logDatabaseError($e->errorInfo, $deleteEventQuery ?? $deleteRegistrationsQuery ?? '', [$eventId, $userId]);
            $this->errors['database'] = 'Database error: ' . $e->getMessage();
            return false;
        }
    }

    /**
     * Validate event data before creation or update
     * Checks title, description, date, location, capacity, and status
     */
    public function validate($data)
    {
        logger()->debug('Validating event data', [
            'title' => $data['title'],
            'event_date' => $data['event_date']
        ]);

        $this->errors = [];

        // Title validation
        if (empty($data['title'])) {
            $this->errors['title'] = 'Title is required';
            logger()->warning('Event validation failed: Empty title');
        } elseif (strlen($data['title']) > 100) {
            $this->errors['title'] = 'Title must be less than 100 characters';
            logger()->warning('Event validation failed: Title too long', [
                'length' => strlen($data['title'])
            ]);
        }

        // Description validation
        if (empty($data['description'])) {
            $this->errors['description'] = 'Description is required';
            logger()->warning('Event validation failed: Empty description');
        }

        // Event date validation
        if (empty($data['event_date'])) {
            $this->errors['event_date'] = 'Event date is required';
            logger()->warning('Event validation failed: Empty date');
        } else {
            $eventDate = strtotime($data['event_date']);
            $now = time();
            if ($eventDate < $now) {
                $this->errors['event_date'] = 'Event date cannot be in the past';
                logger()->warning('Event validation failed: Past date', [
                    'event_date' => $data['event_date']
                ]);
            }
        }

        // Location validation
        if (empty($data['location'])) {
            $this->errors['location'] = 'Location is required';
            logger()->warning('Event validation failed: Empty location');
        } elseif (strlen($data['location']) > 255) {
            $this->errors['location'] = 'Location must be less than 255 characters';
            logger()->warning('Event validation failed: Location too long', [
                'length' => strlen($data['location'])
            ]);
        }

        // Capacity validation
        if (empty($data['capacity'])) {
            $this->errors['capacity'] = 'Capacity is required';
            logger()->warning('Event validation failed: Empty capacity');
        } elseif (!is_numeric($data['capacity']) || $data['capacity'] < 1) {
            $this->errors['capacity'] = 'Capacity must be a positive number';
            logger()->warning('Event validation failed: Invalid capacity', [
                'capacity' => $data['capacity']
            ]);
        }

        // Status validation
        $validStatuses = ['draft', 'published', 'cancelled'];
        if (!in_array($data['status'], $validStatuses)) {
            $this->errors['status'] = 'Invalid status';
            logger()->warning('Event validation failed: Invalid status', [
                'status' => $data['status']
            ]);
        }

        $isValid = empty($this->errors);
        if ($isValid) {
            logger()->info('Event validation passed', [
                'title' => $data['title']
            ]);
        }

        return $isValid;
    }

    /**
     * Get registration status of a specific user for an event
     */
    public function getUserRegistrationStatus($eventId, $userId)
    {
        try {
            logger()->debug('Checking user registration status', [
                'eventId' => $eventId,
                'userId' => $userId
            ]);

            $query = "SELECT * FROM registrations WHERE event_id = ? AND user_id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$eventId, $userId]);
            $registration = $stmt->fetch();

            if ($registration) {
                logger()->info('Found user registration', [
                    'eventId' => $eventId,
                    'userId' => $userId,
                    'status' => $registration['status']
                ]);
            } else {
                logger()->debug('No registration found', [
                    'eventId' => $eventId,
                    'userId' => $userId
                ]);
            }

            return $registration;
        } catch (PDOException $e) {
            logDatabaseError($e->errorInfo, $query, [$eventId, $userId]);
            $this->errors['database'] = 'Error checking registration status: ' . $e->getMessage();
            return false;
        }
    }

    /**
     * Register a user for an event
     * Handles capacity checks and waitlist functionality
     */
    public function registerUser($eventId, $userId, $status = 'pending')
    {
        try {
            logger()->info('Attempting to register user for event', [
                'eventId' => $eventId,
                'userId' => $userId,
                'status' => $status
            ]);

            // Check for existing registration
            $checkQuery = "SELECT COUNT(*) FROM registrations WHERE user_id = ? AND event_id = ?";
            $checkStmt = $this->db->prepare($checkQuery);
            $checkStmt->execute([$userId, $eventId]);

            if ($checkStmt->fetchColumn() > 0) {
                logger()->warning('User already registered', [
                    'eventId' => $eventId,
                    'userId' => $userId
                ]);
                $this->errors['registration'] = 'User is already registered for this event';
                return false;
            }

            // Check event capacity and handle waitlist
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
                if (isset($_POST['waitlist'])) {
                    $status = 'waitlisted';
                    logger()->info('Adding user to waitlist (capacity full)', [
                        'eventId' => $eventId,
                        'userId' => $userId,
                        'currentRegistrations' => $capacityInfo['current_registrations'],
                        'capacity' => $capacityInfo['capacity']
                    ]);
                } else {
                    logger()->warning('Registration failed - capacity full', [
                        'eventId' => $eventId,
                        'userId' => $userId,
                        'currentRegistrations' => $capacityInfo['current_registrations'],
                        'capacity' => $capacityInfo['capacity']
                    ]);
                    $this->errors['registration'] = 'Event has reached maximum capacity';
                    return false;
                }
            }

            // Create the registration
            $registrationQuery = "INSERT INTO registrations (user_id, event_id, status) 
                                VALUES (?, ?, ?)";

            $registrationStmt = $this->db->prepare($registrationQuery);
            $registrationStmt->execute([$userId, $eventId, $status]);

            logger()->info('User registration successful', [
                'eventId' => $eventId,
                'userId' => $userId,
                'status' => $status
            ]);

            return true;
        } catch (PDOException $e) {
            logDatabaseError($e->errorInfo, $registrationQuery ?? '', [$userId, $eventId, $status]);
            $this->errors['registration'] = 'Registration failed: ' . $e->getMessage();
            return false;
        }
    }

    /**
     * Get all registrations for events created by a user
     * Returns pending and waitlisted registrations for review
     */
    public function getEventRegistrations($userId)
    {
        try {
            logger()->debug('Fetching event registrations', [
                'organizerId' => $userId
            ]);

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
            $registrations = $stmt->fetchAll();

            logger()->info('Successfully fetched event registrations', [
                'organizerId' => $userId,
                'count' => count($registrations)
            ]);

            return $registrations;
        } catch (PDOException $e) {
            logDatabaseError($e->errorInfo, $query, [$userId]);
            $this->errors['database'] = 'Error fetching registrations: ' . $e->getMessage();
            return false;
        }
    }

    /**
     * Update registration status and handle capacity management
     * Only event creator can update registration status
     */
    public function updateRegistrationStatus($registrationId, $newStatus, $userId)
    {
        try {
            logger()->info('Attempting to update registration status', [
                'registrationId' => $registrationId,
                'newStatus' => $newStatus,
                'userId' => $userId
            ]);

            // Verify event ownership and check capacity
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
                logger()->warning('Unauthorized registration status update attempt', [
                    'registrationId' => $registrationId,
                    'userId' => $userId
                ]);
                $this->errors['permission'] = 'You do not have permission to update this registration';
                return false;
            }

            // Check capacity before confirming
            if (
                $newStatus === 'confirmed' &&
                $registration['confirmed_count'] >= $registration['capacity']
            ) {
                logger()->warning('Cannot confirm registration - capacity full', [
                    'registrationId' => $registrationId,
                    'eventId' => $registration['event_id'],
                    'currentConfirmed' => $registration['confirmed_count'],
                    'capacity' => $registration['capacity']
                ]);
                $this->errors['capacity'] = 'Event has reached maximum capacity';
                return false;
            }

            $query = "UPDATE registrations SET status = ? WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$newStatus, $registrationId]);

            logger()->info('Registration status updated successfully', [
                'registrationId' => $registrationId,
                'newStatus' => $newStatus,
                'eventId' => $registration['event_id']
            ]);

            return true;
        } catch (PDOException $e) {
            logDatabaseError($e->errorInfo, $query, [$newStatus, $registrationId]);
            $this->errors['database'] = 'Error updating registration: ' . $e->getMessage();
            return false;
        }
    }

    public function getErrors()
    {
        return $this->errors;
    }
}
