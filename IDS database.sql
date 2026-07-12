CREATE DATABASE helpdesk_pro;

USE helpdesk_pro;

-- ==========================
-- 1. ROLES TABLE
-- ==========================
CREATE TABLE roles (
    role_id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


-- ==========================
-- 2. DEPARTMENTS TABLE
-- ==========================
CREATE TABLE departments (
    department_id INT AUTO_INCREMENT PRIMARY KEY,
    department_name VARCHAR(100) NOT NULL UNIQUE,
    description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


-- ==========================
-- 3. USERS TABLE
-- ==========================
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    role_id INT NOT NULL,
    department_id INT,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    status ENUM('Active','Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (role_id) REFERENCES roles(role_id),
    FOREIGN KEY (department_id) REFERENCES departments(department_id)
);


-- ==========================
-- 4. CATEGORIES TABLE
-- ==========================
CREATE TABLE categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    description VARCHAR(255),
    sla_hours INT,
    status ENUM('Active','Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


-- ==========================
-- 5. TICKETS TABLE
-- ==========================
CREATE TABLE tickets (
    ticket_id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_number VARCHAR(20) UNIQUE NOT NULL,
    title VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,

    category_id INT NOT NULL,
    created_by INT NOT NULL,
    assigned_to INT,

    priority ENUM('Low','Medium','High','Critical') DEFAULT 'Medium',
    status ENUM(
        'Open',
        'Assigned',
        'In Progress',
        'Resolved',
        'Closed'
    ) DEFAULT 'Open',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    closed_at TIMESTAMP NULL,

    FOREIGN KEY (category_id) REFERENCES categories(category_id),
    FOREIGN KEY (created_by) REFERENCES users(user_id),
    FOREIGN KEY (assigned_to) REFERENCES users(user_id)
);


-- ==========================
-- 6. TICKET COMMENTS
-- ==========================
CREATE TABLE ticket_comments (
    comment_id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    user_id INT NOT NULL,
    comment_text TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (ticket_id) REFERENCES tickets(ticket_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);


-- ==========================
-- 7. TICKET ATTACHMENTS
-- ==========================
CREATE TABLE ticket_attachments (
    attachment_id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    uploaded_by INT NOT NULL,
    file_name VARCHAR(255),
    file_path VARCHAR(255),
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (ticket_id) REFERENCES tickets(ticket_id),
    FOREIGN KEY (uploaded_by) REFERENCES users(user_id)
);


-- ==========================
-- 8. TICKET HISTORY
-- ==========================
CREATE TABLE ticket_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    changed_by INT NOT NULL,

    old_status VARCHAR(50),
    new_status VARCHAR(50),
    comment VARCHAR(255),

    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (ticket_id) REFERENCES tickets(ticket_id),
    FOREIGN KEY (changed_by) REFERENCES users(user_id)
);


-- ==========================
-- 9. NOTIFICATIONS TABLE
-- ==========================
CREATE TABLE notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(150),
    message TEXT,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(user_id)
);


-- ==========================
-- 10. ACTIVITY LOGS
-- ==========================
CREATE TABLE activity_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(100),
    description TEXT,
    ip_address VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(user_id)
);


-- ==========================
-- 11. SLA RULES
-- ==========================
CREATE TABLE sla_rules (
    sla_id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    priority VARCHAR(50),
    response_time INT,
    resolution_time INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (category_id) REFERENCES categories(category_id)
);


-- ==========================
-- 12. USER SESSIONS
-- ==========================
CREATE TABLE user_sessions (
    session_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    ip_address VARCHAR(50),
    device VARCHAR(100),
    login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    logout_time TIMESTAMP NULL,

    FOREIGN KEY (user_id) REFERENCES users(user_id)
);




USE helpdesk_pro;

-- ==========================
-- INSERT ROLES
-- ==========================
INSERT INTO roles (role_name, description)
VALUES
('Employee', 'Regular company employee who creates support tickets'),
('IT Support', 'Support agent who manages and resolves tickets'),
('Manager', 'Manager who monitors reports and team performance'),
('Admin', 'System administrator with full access');


-- ==========================
-- INSERT DEPARTMENTS
-- ==========================
INSERT INTO departments (department_name, description)
VALUES
('Information Technology', 'Technical department'),
('Human Resources', 'Employee management department'),
('Finance', 'Financial operations department'),
('Marketing', 'Marketing and communication department'),
('Operations', 'Daily business operations department');


-- ==========================
-- INSERT CATEGORIES
-- ==========================
INSERT INTO categories 
(category_name, description, sla_hours)
VALUES
('Hardware', 'Laptop, printer, and device issues', 4),
('Software', 'Application installation and software problems', 3),
('Network', 'Internet, WiFi, and connectivity issues', 2),
('Email', 'Email and communication problems', 4),
('Access Request', 'Account and permission requests', 8);




SELECT * FROM roles;

SELECT * FROM departments;

SELECT * FROM categories;