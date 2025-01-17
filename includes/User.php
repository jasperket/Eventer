<?php
// Required dependencies for database, logging and utilities
require_once 'Database.php';
require_once 'Logger.php';
require_once 'logger-utils.php';

/**
 * User class handles user authentication and registration
 */
class User
{
    private $db;
    private $errors = []; // Stores validation and operation errors

    /**
     * Initialize database connection
     */
    public function __construct()
    {
        try {
            $this->db = Database::getInstance()->getConnection();
        } catch (Exception $e) {
            logException($e, 'Failed to initialize User class');
            throw $e;
        }
    }

    /**
     * Validate user registration data
     * Checks username, email and password requirements
     */
    public function validateRegistration($data)
    {
        $this->errors = [];
        logger()->debug('Validating user registration data', ['username' => $data['username'], 'email' => $data['email']]);

        // Username validation
        if (empty($data['username'])) {
            $this->errors['username'] = 'Username is required';
            logger()->warning('Registration validation failed: Empty username');
        } elseif (strlen($data['username']) < 3 || strlen($data['username']) > 50) {
            $this->errors['username'] = 'Username must be between 3 and 50 characters';
            logger()->warning('Registration validation failed: Invalid username length', [
                'length' => strlen($data['username'])
            ]);
        } elseif ($this->usernameExists($data['username'])) {
            $this->errors['username'] = 'Username already exists';
            logger()->warning('Registration validation failed: Username exists', [
                'username' => $data['username']
            ]);
        }

        // Email validation
        if (empty($data['email'])) {
            $this->errors['email'] = 'Email is required';
            logger()->warning('Registration validation failed: Empty email');
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $this->errors['email'] = 'Invalid email format';
            logger()->warning('Registration validation failed: Invalid email format', [
                'email' => $data['email']
            ]);
        } elseif ($this->emailExists($data['email'])) {
            $this->errors['email'] = 'Email already exists';
            logger()->warning('Registration validation failed: Email exists', [
                'email' => $data['email']
            ]);
        }

        // Password validation
        if (empty($data['password'])) {
            $this->errors['password'] = 'Password is required';
            logger()->warning('Registration validation failed: Empty password');
        } elseif (strlen($data['password']) < 6) {
            $this->errors['password'] = 'Password must be at least 6 characters';
            logger()->warning('Registration validation failed: Password too short');
        }

        $isValid = empty($this->errors);
        if ($isValid) {
            logger()->info('Registration validation passed', [
                'username' => $data['username'],
                'email' => $data['email']
            ]);
        }

        return $isValid;
    }

    /**
     * Check if username already exists in database
     */
    private function usernameExists($username)
    {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $stmt->execute([$username]);
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            logDatabaseError($e->errorInfo, "Check username exists", ['username' => $username]);
            throw $e;
        }
    }

    /**
     * Check if email already exists in database
     */
    private function emailExists($email)
    {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
            $stmt->execute([$email]);
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            logDatabaseError($e->errorInfo, "Check email exists", ['email' => $email]);
            throw $e;
        }
    }

    /**
     * Register new user with validated data
     * Returns user ID on success, false on failure
     */
    public function register($data)
    {
        if (!$this->validateRegistration($data)) {
            return false;
        }

        try {
            $this->db->beginTransaction();

            logger()->info('Attempting user registration', [
                'username' => $data['username'],
                'email' => $data['email']
            ]);

            $query = "INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)";
            $stmt = $this->db->prepare($query);

            $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);

            $stmt->execute([
                $data['username'],
                $data['email'],
                $passwordHash,
                'user' // Default role
            ]);

            $userId = $this->db->lastInsertId();

            $this->db->commit();

            logger()->info('User registered successfully', [
                'userId' => $userId,
                'username' => $data['username'],
                'email' => $data['email']
            ]);

            return $userId;
        } catch (PDOException $e) {
            $this->db->rollback();
            logDatabaseError($e->errorInfo, $query ?? '', [
                'username' => $data['username'],
                'email' => $data['email']
            ]);
            $this->errors['database'] = 'Registration failed: ' . $e->getMessage();
            return false;
        }
    }

    /**
     * Authenticate user and create session
     * Returns true on success, false on failure
     */
    public function login($username, $password)
    {
        try {
            logger()->info('Login attempt', ['username' => $username]);

            $query = "SELECT id, username, email, password_hash, role FROM users WHERE username = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$username]);

            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                // Remove password hash before storing in session
                unset($user['password_hash']);

                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];

                logger()->info('Login successful', [
                    'userId' => $user['id'],
                    'username' => $user['username'],
                    'role' => $user['role']
                ]);

                return true;
            }

            logger()->warning('Login failed: Invalid credentials', ['username' => $username]);
            $this->errors['login'] = 'Invalid username or password';
            return false;
        } catch (PDOException $e) {
            logDatabaseError($e->errorInfo, $query ?? '', ['username' => $username]);
            $this->errors['database'] = 'Login failed: ' . $e->getMessage();
            return false;
        }
    }

    /**
     * Get any errors that occurred during operations
     */
    public function getErrors()
    {
        return $this->errors;
    }
}
