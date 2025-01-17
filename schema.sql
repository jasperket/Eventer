-- Drop tables if they exist (in correct order due to foreign keys)
DROP TABLE IF EXISTS registrations;
DROP TABLE IF EXISTS events;
DROP TABLE IF EXISTS users;

-- Create users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'organizer', 'user') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create events table
CREATE TABLE events (
    id INT PRIMARY KEY AUTO_INCREMENT,
    creator_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    event_date DATETIME NOT NULL,
    location VARCHAR(255) NOT NULL,
    capacity INT NOT NULL CHECK (capacity > 0),
    status ENUM('draft', 'published', 'cancelled', 'completed') NOT NULL DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (creator_id) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_event_date (event_date),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create registrations table
CREATE TABLE registrations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    event_id INT NOT NULL,
    status ENUM('pending', 'confirmed', 'cancelled', 'waitlisted') NOT NULL DEFAULT 'pending',
    registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE RESTRICT,
    UNIQUE KEY unique_user_event (user_id, event_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample admin user (password: admin123)
INSERT INTO users (username, email, password_hash, role) VALUES 
('admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Insert sample event
INSERT INTO events (creator_id, title, description, event_date, location, capacity, status) VALUES 
(1, 'PHP Developer Meetup', 'Monthly meetup for PHP developers', '2025-02-01 18:00:00', 'Tech Hub, Downtown', 50, 'published');

-- Insert sample registration
INSERT INTO registrations (user_id, event_id, status) VALUES 
(1, 1, 'confirmed');

-- Add indexes to users table for role-based queries
ALTER TABLE users ADD INDEX idx_role (role);
ALTER TABLE users ADD INDEX idx_created_at (created_at);

-- Add indexes to events table for common queries and sorting
ALTER TABLE events ADD INDEX idx_creator_status (creator_id, status);
ALTER TABLE events ADD INDEX idx_created_at (created_at);
ALTER TABLE events ADD INDEX idx_upcoming_events (status, event_date);
ALTER TABLE events ADD INDEX idx_capacity (capacity);

-- Add indexes to registrations table for status checks and event management
ALTER TABLE registrations ADD INDEX idx_user_status (user_id, status);
ALTER TABLE registrations ADD INDEX idx_event_status (event_id, status);
ALTER TABLE registrations ADD INDEX idx_registered_at (registered_at);

-- Add composite indexes for common joins and complex queries
ALTER TABLE events ADD INDEX idx_event_search (status, event_date, creator_id);
ALTER TABLE registrations ADD INDEX idx_registration_management (event_id, status, registered_at);