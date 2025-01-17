<?php
// File: includes/logger-utils.php

/**
 * Helper function to get logger instance
 */
function logger()
{
    return Logger::getInstance();
}

/**
 * Helper function for logging exceptions with full details
 */
function logException(Throwable $e, string $context = '')
{
    $logger = logger();
    $message = sprintf(
        "%s: [%s] %s in %s:%d\nStack trace:\n%s",
        $context,
        get_class($e),
        $e->getMessage(),
        $e->getFile(),
        $e->getLine(),
        $e->getTraceAsString()
    );
    $logger->error($message);
}

/**
 * Helper function for logging database errors
 */
function logDatabaseError($errorInfo, string $query = '', array $params = [])
{
    $logger = logger();
    $context = [
        'query' => $query,
        'params' => $params,
        'errorCode' => $errorInfo[0],
        'sqlState' => $errorInfo[1],
        'errorMessage' => $errorInfo[2]
    ];
    $logger->error('Database error occurred', $context);
}

/**
 * Helper function for logging authentication events
 */
function logAuthEvent(string $event, string $username, bool $success = true)
{
    $logger = logger();
    $context = [
        'username' => $username,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'userAgent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ];
    $message = sprintf('Authentication %s: %s', $success ? 'success' : 'failure', $event);
    $logger->info($message, $context);
}

/**
 * Helper function for logging event management actions
 */
function logEventAction(string $action, int $eventId, array $details = [])
{
    $logger = logger();
    $context = array_merge([
        'eventId' => $eventId,
        'userId' => $_SESSION['user_id'] ?? null,
    ], $details);
    $logger->info("Event $action", $context);
}

/**
 * Helper function for logging registration actions
 */
function logRegistrationAction(string $action, int $eventId, int $userId, string $status, array $details = [])
{
    $logger = logger();
    $context = array_merge([
        'eventId' => $eventId,
        'userId' => $userId,
        'status' => $status
    ], $details);
    $logger->info("Registration $action", $context);
}
