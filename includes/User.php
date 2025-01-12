<?php
require_once 'Database.php';

class User {
    private $db;
    private $errors = [];

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function validateRegistration($data) {
        $this->errors = [];

        // Username validation
        if (empty($data['username'])) {
            $this->errors['username'] = 'Username is required';
        } elseif (strlen($data['username']) < 3 || strlen($data['username']) > 50) {
            $this->errors['username'] = 'Username must be between 3 and 50 characters';
        } elseif ($this->usernameExists($data['username'])) {
            $this->errors['username'] = 'Username already exists';
        }

        // Email validation
        if (empty($data['email'])) {
            $this->errors['email'] = 'Email is required';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $this->errors['email'] = 'Invalid email format';
        } elseif ($this->emailExists($data['email'])) {
            $this->errors['email'] = 'Email already exists';
        }

        // Password validation
        if (empty($data['password'])) {
            $this->errors['password'] = 'Password is required';
        } elseif (strlen($data['password']) < 6) {
            $this->errors['password'] = 'Password must be at least 6 characters';
        }

        return empty($this->errors);
    }

    private function usernameExists($username) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetchColumn() > 0;
    }

    private function emailExists($email) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetchColumn() > 0;
    }

    public function register($data) {
        if (!$this->validateRegistration($data)) {
            return false;
        }

        try {
            $query = "INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)";
            $stmt = $this->db->prepare($query);
            
            $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
            
            $stmt->execute([
                $data['username'],
                $data['email'],
                $passwordHash,
                'user' // Default role
            ]);

            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            $this->errors['database'] = 'Registration failed: ' . $e->getMessage();
            return false;
        }
    }

    public function login($username, $password) {
        try {
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
                
                return true;
            }
            
            $this->errors['login'] = 'Invalid username or password';
            return false;
        } catch (PDOException $e) {
            $this->errors['database'] = 'Login failed: ' . $e->getMessage();
            return false;
        }
    }

    public function getErrors() {
        return $this->errors;
    }
}