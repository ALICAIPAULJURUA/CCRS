-- Create database
CREATE DATABASE IF NOT EXISTS ccrs_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ccrs_db;

-- Roles table
CREATE TABLE roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    role_name VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default roles
INSERT INTO roles (role_name, description) VALUES
('admin', 'System Administrator'),
('law_enforcement', 'Police Officer'),
('child_protection', 'Child Protection Officer'),
('ngo', 'NGO Representative'),
('lc1', 'Local Council Member'),
('counselor', 'Professional Counselor');

-- System users table
CREATE TABLE system_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE,
    full_name VARCHAR(100),
    role_id INT,
    is_active BOOLEAN DEFAULT TRUE,
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id),
    INDEX idx_role (role_id),
    INDEX idx_active (is_active)
);

-- Report categories
CREATE TABLE report_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_name VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE
);

-- Insert default categories
INSERT INTO report_categories (category_name, description) VALUES
('Gender-Based Violence - Physical', 'Physical assault, battery, or physical harm'),
('Gender-Based Violence - Sexual', 'Sexual assault, harassment, or rape'),
('Gender-Based Violence - Emotional', 'Psychological abuse, threats, intimidation'),
('Gender-Based Violence - Economic', 'Financial control, denial of resources'),
('Child Abuse - Physical', 'Physical harm to a child'),
('Child Abuse - Sexual', 'Sexual abuse of a minor'),
('Child Abuse - Neglect', 'Failure to provide basic needs'),
('Child Abuse - Emotional', 'Psychological harm to a child');

-- Main reports table
CREATE TABLE reports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tracking_id VARCHAR(20) UNIQUE NOT NULL,
    category_id INT,
    incident_type VARCHAR(100),
    description TEXT NOT NULL,
    location VARCHAR(255) NOT NULL,
    incident_date DATE NOT NULL,
    incident_time TIME,
    submission_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'under_investigation', 'resolved', 'closed') DEFAULT 'pending',
    consent_counselor BOOLEAN DEFAULT FALSE,
    is_anonymous BOOLEAN DEFAULT TRUE,
    ip_address VARCHAR(45),
    user_agent TEXT,
    assigned_to INT,
    assigned_date DATETIME,
    resolved_date DATETIME,
    FOREIGN KEY (category_id) REFERENCES report_categories(id),
    FOREIGN KEY (assigned_to) REFERENCES system_users(id),
    INDEX idx_tracking (tracking_id),
    INDEX idx_status (status),
    INDEX idx_date (submission_date),
    INDEX idx_location (location(100))
);

-- Evidence files
CREATE TABLE evidence_files (
    id INT PRIMARY KEY AUTO_INCREMENT,
    report_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type ENUM('image', 'audio', 'document') NOT NULL,
    file_size INT,
    mime_type VARCHAR(100),
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE CASCADE,
    INDEX idx_report (report_id)
);

-- Case notes (for investigators)
CREATE TABLE case_notes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    report_id INT NOT NULL,
    user_id INT NOT NULL,
    note TEXT NOT NULL,
    is_private BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES system_users(id),
    INDEX idx_report_notes (report_id)
);

-- Status history
CREATE TABLE status_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    report_id INT NOT NULL,
    old_status VARCHAR(50),
    new_status VARCHAR(50) NOT NULL,
    changed_by INT,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES system_users(id),
    INDEX idx_report_status (report_id)
);

-- Counseling sessions
CREATE TABLE counseling_sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    report_id INT NOT NULL,
    counselor_id INT,
    survivor_identifier VARCHAR(100), -- Anonymous identifier
    status ENUM('requested', 'active', 'completed', 'cancelled') DEFAULT 'requested',
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    started_at DATETIME,
    completed_at DATETIME,
    FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE CASCADE,
    FOREIGN KEY (counselor_id) REFERENCES system_users(id),
    INDEX idx_counselor (counselor_id),
    INDEX idx_status_sessions (status)
);

-- Counseling messages
CREATE TABLE counseling_messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    session_id INT NOT NULL,
    sender_type ENUM('survivor', 'counselor') NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES counseling_sessions(id) ON DELETE CASCADE,
    INDEX idx_session (session_id)
);

-- Audit logs
CREATE TABLE audit_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(50),
    record_id INT,
    old_value TEXT,
    new_value TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES system_users(id),
    INDEX idx_user_audit (user_id),
    INDEX idx_action_audit (action),
    INDEX idx_date_audit (created_at)
);

-- Notifications
CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'success', 'warning', 'danger') DEFAULT 'info',
    is_read BOOLEAN DEFAULT FALSE,
    link VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES system_users(id) ON DELETE CASCADE,
    INDEX idx_user_notif (user_id),
    INDEX idx_read_notif (is_read)
);

-- System configuration
CREATE TABLE system_config (
    id INT PRIMARY KEY AUTO_INCREMENT,
    config_key VARCHAR(100) UNIQUE NOT NULL,
    config_value TEXT,
    description TEXT,
    updated_by INT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES system_users(id)
);

-- Insert default system config
INSERT INTO system_config (config_key, config_value, description) VALUES
('site_name', 'Community Crime Reporting System', 'Website name'),
('contact_email', 'support@ccrs.ug', 'Support email address'),
('emergency_phone', '+256-XXX-XXXXXX', 'Emergency contact number'),
('max_file_size', '10485760', 'Maximum file size in bytes (10MB)'),
('allowed_file_types', 'jpg,jpeg,png,gif,mp3,wav,ogg,pdf,doc,docx', 'Allowed file extensions'),
('session_timeout', '3600', 'Session timeout in seconds'),
('tracking_id_prefix', 'CCRS', 'Prefix for tracking IDs');

-- Create default admin user (password: Admin@123)
INSERT INTO system_users (username, password_hash, email, full_name, role_id) VALUES
('admin', '$2y$10$YourHashHere', 'admin@ccrs.ug', 'System Administrator', 1);

-- Statistics views for easier reporting
CREATE VIEW vw_report_stats AS
SELECT 
    DATE(submission_date) as report_date,
    COUNT(*) as total_reports,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'under_investigation' THEN 1 ELSE 0 END) as investigating,
    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved,
    SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed
FROM reports
GROUP BY DATE(submission_date);

CREATE VIEW vw_category_stats AS
SELECT 
    rc.category_name,
    COUNT(r.id) as total_cases,
    SUM(CASE WHEN r.status = 'resolved' THEN 1 ELSE 0 END) as resolved_cases
FROM report_categories rc
LEFT JOIN reports r ON rc.id = r.category_id
GROUP BY rc.id, rc.category_name;