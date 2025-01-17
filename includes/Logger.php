<?php
class Logger
{
    private static $instance = null;
    private $logDirectory;
    private $defaultLogFile;
    private $logLevel;

    // Log levels in order of severity (lower number = more severe)
    const LOG_LEVELS = [
        'ERROR' => 0,
        'WARNING' => 1,
        'INFO' => 2,
        'DEBUG' => 3
    ];

    private function __construct()
    {
        // Set log directory relative to project root
        $this->logDirectory = dirname(__DIR__) . '/logs';
        $this->defaultLogFile = $this->logDirectory . '/app.log';
        $this->logLevel = self::LOG_LEVELS['INFO']; // Default log level

        // Create logs directory if it doesn't exist
        if (!file_exists($this->logDirectory)) {
            mkdir($this->logDirectory, 0755, true);
        }

        // Create log file if it doesn't exist
        if (!file_exists($this->defaultLogFile)) {
            touch($this->defaultLogFile);
            chmod($this->defaultLogFile, 0644);
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function setLogLevel($level)
    {
        if (!array_key_exists($level, self::LOG_LEVELS)) {
            throw new InvalidArgumentException("Invalid log level: $level");
        }
        $this->logLevel = self::LOG_LEVELS[$level];
    }

    private function shouldLog($messageLevel)
    {
        return self::LOG_LEVELS[$messageLevel] <= $this->logLevel;
    }

    private function formatMessage($level, $message, $context = [])
    {
        $timestamp = date('Y-m-d H:i:s');
        $requestId = substr(uniqid(), -6); // Generate a unique request ID
        $userInfo = isset($_SESSION['user_id']) ? "User:{$_SESSION['user_id']}" : 'Guest';

        // Format context data
        $contextStr = empty($context) ? '' : ' ' . json_encode($context);

        // Include request method and URI if available
        $request = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'CLI';
        $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';

        return sprintf(
            "[%s] [%s] [%s] [%s] [%s%s] %s%s\n",
            $timestamp,
            $requestId,
            $level,
            $userInfo,
            $request,
            $uri,
            $message,
            $contextStr
        );
    }

    private function writeLog($message)
    {
        $handle = fopen($this->defaultLogFile, 'a');
        if ($handle === false) {
            error_log("Failed to open log file: {$this->defaultLogFile}");
            return false;
        }

        if (flock($handle, LOCK_EX)) {
            fwrite($handle, $message);
            flock($handle, LOCK_UN);
        } else {
            error_log("Failed to acquire lock on log file: {$this->defaultLogFile}");
        }

        fclose($handle);
        return true;
    }

    public function log($level, $message, array $context = [])
    {
        if (!$this->shouldLog($level)) {
            return;
        }

        $formattedMessage = $this->formatMessage($level, $message, $context);
        return $this->writeLog($formattedMessage);
    }

    // Convenience methods for different log levels
    public function error($message, array $context = [])
    {
        return $this->log('ERROR', $message, $context);
    }

    public function warning($message, array $context = [])
    {
        return $this->log('WARNING', $message, $context);
    }

    public function info($message, array $context = [])
    {
        return $this->log('INFO', $message, $context);
    }

    public function debug($message, array $context = [])
    {
        return $this->log('DEBUG', $message, $context);
    }

    // Log rotation and maintenance
    public function rotateLogs()
    {
        if (!file_exists($this->defaultLogFile)) {
            return;
        }

        $maxSize = 5 * 1024 * 1024; // 5MB
        if (filesize($this->defaultLogFile) < $maxSize) {
            return;
        }

        $backupFile = $this->defaultLogFile . '.' . date('Y-m-d-H-i-s') . '.bak';
        rename($this->defaultLogFile, $backupFile);
        touch($this->defaultLogFile);
        chmod($this->defaultLogFile, 0644);

        // Clean up old backup files (keep last 5)
        $backupFiles = glob($this->defaultLogFile . '.*.bak');
        if (count($backupFiles) > 5) {
            rsort($backupFiles);
            $filesToDelete = array_slice($backupFiles, 5);
            foreach ($filesToDelete as $file) {
                unlink($file);
            }
        }
    }
}
