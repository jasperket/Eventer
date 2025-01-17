<?php
require_once 'Logger.php';
require_once 'logger-utils.php';

class Database
{
    private static $instance = null;
    private $pdo;

    private function __construct()
    {
        try {
            $dsn = "mysql:host=localhost;dbname=eventmgmt";
            $username = "root";
            $password = "0906";

            logger()->info('Attempting database connection', [
                'host' => 'localhost',
                'database' => 'eventmgmt'
            ]);

            $this->pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);

            // Set up error handling for PDO
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            logger()->info('Database connection established successfully');
        } catch (PDOException $e) {
            logException($e, 'Database connection failed');
            throw new Exception('Connection failed: Database service might be down or credentials are invalid');
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection()
    {
        return $this->pdo;
    }

    /**
     * Begin a transaction with logging
     */
    public function beginTransaction()
    {
        try {
            $result = $this->pdo->beginTransaction();
            logger()->debug('Database transaction started');
            return $result;
        } catch (PDOException $e) {
            logException($e, 'Failed to start database transaction');
            throw $e;
        }
    }

    /**
     * Commit a transaction with logging
     */
    public function commit()
    {
        try {
            $result = $this->pdo->commit();
            logger()->debug('Database transaction committed');
            return $result;
        } catch (PDOException $e) {
            logException($e, 'Failed to commit database transaction');
            throw $e;
        }
    }

    /**
     * Rollback a transaction with logging
     */
    public function rollback()
    {
        try {
            $result = $this->pdo->rollBack();
            logger()->warning('Database transaction rolled back');
            return $result;
        } catch (PDOException $e) {
            logException($e, 'Failed to rollback database transaction');
            throw $e;
        }
    }

    /**
     * Execute a query with logging
     */
    public function executeQuery($query, $params = [])
    {
        try {
            $start = microtime(true);

            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);

            $duration = microtime(true) - $start;

            // Log query execution
            logger()->debug('Query executed', [
                'query' => $this->sanitizeQuery($query),
                'params' => $this->sanitizeParams($params),
                'duration' => round($duration * 1000, 2) . 'ms',
                'affected_rows' => $stmt->rowCount()
            ]);

            return $stmt;
        } catch (PDOException $e) {
            logDatabaseError($e->errorInfo, $query, $params);
            throw $e;
        }
    }

    /**
     * Sanitize sensitive information from query for logging
     */
    private function sanitizeQuery($query)
    {
        // Remove potential sensitive information like passwords
        $patterns = [
            '/password\s*=\s*\'[^\']*\'/i' => 'password=\'***\'',
            '/password_hash\s*=\s*\'[^\']*\'/i' => 'password_hash=\'***\'',
        ];

        return preg_replace(array_keys($patterns), array_values($patterns), $query);
    }

    /**
     * Sanitize sensitive information from parameters for logging
     */
    private function sanitizeParams($params)
    {
        $sanitized = [];
        foreach ($params as $key => $value) {
            // Mask sensitive parameter values
            if (
                stripos($key, 'password') !== false ||
                stripos($key, 'token') !== false ||
                stripos($key, 'secret') !== false
            ) {
                $sanitized[$key] = '***';
            } else {
                $sanitized[$key] = $value;
            }
        }
        return $sanitized;
    }

    /**
     * Check database connection health
     */
    public function checkConnection()
    {
        try {
            $this->pdo->query('SELECT 1');
            logger()->debug('Database connection health check passed');
            return true;
        } catch (PDOException $e) {
            logException($e, 'Database connection health check failed');
            return false;
        }
    }
}
