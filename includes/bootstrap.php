<?php
// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set default timezone
date_default_timezone_set('UTC');

// Define constants
define('ROOT_PATH', dirname(__DIR__));

// Required files
require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/logger-utils.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/User.php';
require_once __DIR__ . '/Event.php';

// Initialize logger
logger()->setLogLevel('DEBUG'); // Set this based on your needs