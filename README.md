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
