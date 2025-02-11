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
    if ($event->registerUser($eventId, $_SESSION['user_id'])) {
        $_SESSION['success'] = 'Successfully registered for the event!';
    } else {
        $_SESSION['error'] = $event->getErrors()['registration'] ?? 'Registration failed';
    }

    header('Location: event.php?id=' . $eventId);
    exit();
} else {
    header('Location: events.php');
    exit();
}
