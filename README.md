# Event Management System

A PHP-based event management system that allows users to create, manage, and register for events. Built with vanilla PHP, MySQL, and styled with TailwindCSS.

## Features

### User Management

- User registration and authentication
- Role-based access control (admin, organizer, user)
- Secure password hashing and session management

### Event Management

- Create, read, update, and delete events
- Event status management (draft, published, cancelled, completed)
- Capacity management with waitlist functionality
- Event search and filtering
- Comprehensive event details including date, location, and description

### Registration System

- Event registration with automatic status handling
- Waitlist support when events reach capacity
- Registration status tracking (pending, confirmed, cancelled, waitlisted)
- Automatic waitlist promotion when spots become available

## Installation

1. Clone the repository:

   ```bash
   git clone [repository-url]
   ```

2. Import the database schema:

   ```bash
   mysql -u your_username -p your_database < schema.sql
   ```

3. Configure your database connection in `includes/Database.php`:

   ```php
   $this->pdo = new PDO(
       "mysql:host=your_host;dbname=your_database",
       "your_username",
       "your_password",
       [...]
   );
   ```

4. Set up your web server to point to the project directory.

5. Ensure proper file permissions:
   ```bash
   chmod 755 -R /path/to/project
   chmod 777 -R /path/to/project/uploads  # If you add file uploads
   ```

## Project Structure

```
event-management/
├── css/                    # Stylesheets
├── includes/              # PHP class files and components
│   ├── Database.php      # Database connection handler
│   ├── Event.php         # Event management class
│   ├── User.php          # User management class
│   └── ...               # Layout components
├── *.php                 # Main application files
└── schema.sql            # Database schema
```
