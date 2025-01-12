<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once 'includes/Event.php';

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

    header('Location: view-event.php?id=' . $eventId);
    exit();
} else {
    header('Location: events.php');
    exit();
}