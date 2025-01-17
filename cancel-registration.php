<?php
require_once 'includes/bootstrap.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $eventId = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);

    if (!$eventId) {
        $_SESSION['error'] = 'Invalid event ID';
        header('Location: events.php');
        exit();
    }

    try {
        $db = Database::getInstance()->getConnection();

        // Update registration status to cancelled
        $query = "UPDATE registrations SET status = 'cancelled' 
                 WHERE user_id = ? AND event_id = ? AND status != 'cancelled'";

        $stmt = $db->prepare($query);
        if ($stmt->execute([$_SESSION['user_id'], $eventId])) {
            if ($stmt->rowCount() > 0) {
                $_SESSION['success'] = 'Successfully cancelled your registration';

                // Check if there are any waitlisted registrations
                $waitlistQuery = "SELECT r.user_id 
                                FROM registrations r
                                WHERE r.event_id = ? 
                                AND r.status = 'waitlisted'
                                ORDER BY r.registered_at ASC
                                LIMIT 1";

                $waitlistStmt = $db->prepare($waitlistQuery);
                $waitlistStmt->execute([$eventId]);

                if ($waitlistedUser = $waitlistStmt->fetch()) {
                    // Update the first waitlisted user to confirmed
                    $updateQuery = "UPDATE registrations 
                                  SET status = 'confirmed' 
                                  WHERE user_id = ? AND event_id = ?";

                    $updateStmt = $db->prepare($updateQuery);
                    $updateStmt->execute([$waitlistedUser['user_id'], $eventId]);
                }
            } else {
                $_SESSION['error'] = 'No active registration found';
            }
        } else {
            $_SESSION['error'] = 'Failed to cancel registration';
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Database error: ' . $e->getMessage();
    }

    header('Location: event.php?id=' . $eventId);
    exit();
} else {
    header('Location: events.php');
    exit();
}
