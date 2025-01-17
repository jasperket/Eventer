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

    $event = new Event();
    if ($event->delete($eventId, $_SESSION['user_id'])) {
        $_SESSION['success'] = 'Event deleted successfully';
    } else {
        $_SESSION['error'] = implode(' ', $event->getErrors());
    }

    header('Location: events.php');
    exit();
} else {
    header('Location: events.php');
    exit();
}
