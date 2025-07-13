-- SQL script to create database tables for travel request system
-- Run this script in your MySQL database

CREATE DATABASE IF NOT EXISTS mowaa_db;
USE mowaa_db;

-- Main travel requests table
CREATE TABLE IF NOT EXISTS travel_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id VARCHAR(50) UNIQUE NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    department VARCHAR(100) NOT NULL,
    travel_date DATE NOT NULL,
    departure_airport VARCHAR(100) NOT NULL,
    arrival_airport VARCHAR(100) NOT NULL,
    reason_travel TEXT NOT NULL,
    estimated_cost DECIMAL(10,2) NOT NULL,
    project_name VARCHAR(100) NOT NULL,
    budget_code VARCHAR(50) NOT NULL,
    approver VARCHAR(100) NOT NULL,
    requester VARCHAR(200) NOT NULL,
    passport_file_path VARCHAR(500) NULL,
    additional_files_paths TEXT NULL,
    status ENUM('pending', 'approved', 'rejected', 'cancelled') DEFAULT 'pending',
    approved_by VARCHAR(100) NULL,
    approved_at TIMESTAMP NULL,
    rejection_reason TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_request_id (request_id),
    INDEX idx_email (email),
    INDEX idx_status (status),
    INDEX idx_travel_date (travel_date),
    INDEX idx_created_at (created_at)
);

-- Users table (for approvers and other system users with login functionality)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_code VARCHAR(50) UNIQUE NOT NULL,
    username VARCHAR(100) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    title VARCHAR(100) NOT NULL,
    department VARCHAR(100),
    phone VARCHAR(20),
    profile_picture VARCHAR(500),
    user_role ENUM('admin', 'approver', 'manager', 'user') DEFAULT 'user',
    can_approve BOOLEAN DEFAULT FALSE,
    approval_limit DECIMAL(10,2) DEFAULT 0.00,
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    password_reset_token VARCHAR(255) NULL,
    password_reset_expires TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_user_code (user_code),
    INDEX idx_user_role (user_role),
    INDEX idx_can_approve (can_approve),
    INDEX idx_department (department),
    INDEX idx_is_active (is_active)
);

-- Insert sample users (approvers and admins)
-- Note: All users have default password 'password123' (hashed)
-- Change these passwords after first login for security
INSERT INTO users (user_code, username, email, password_hash, first_name, last_name, title, department, user_role, can_approve, approval_limit) VALUES
('admin1', 'admin', 'admin@mowaa.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System', 'Administrator', 'System Administrator', 'IT', 'admin', TRUE, 999999.99),
('mgr001', 'johnsmith', 'john.smith@mowaa.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John', 'Smith', 'Department Manager', 'Operations', 'manager', TRUE, 10000.00),
('mgr002', 'sarahjohnson', 'sarah.johnson@mowaa.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sarah', 'Johnson', 'Finance Manager', 'Finance', 'manager', TRUE, 25000.00),
('mgr003', 'michaelbrown', 'michael.brown@mowaa.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Michael', 'Brown', 'Operations Manager', 'Operations', 'manager', TRUE, 15000.00),
('dir001', 'lisadavis', 'lisa.davis@mowaa.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Lisa', 'Davis', 'Regional Director', 'Management', 'approver', TRUE, 50000.00);

-- User sessions table for login management
CREATE TABLE IF NOT EXISTS user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_token VARCHAR(255) UNIQUE NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_session_token (session_token),
    INDEX idx_expires_at (expires_at)
);

-- User login attempts table for security
CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100),
    email VARCHAR(255),
    ip_address VARCHAR(45),
    success BOOLEAN DEFAULT FALSE,
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_ip_address (ip_address),
    INDEX idx_attempted_at (attempted_at)
);

-- Comments table for approval workflow
CREATE TABLE IF NOT EXISTS travel_request_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    user_name VARCHAR(100) NOT NULL,
    user_email VARCHAR(255) NOT NULL,
    comment TEXT NOT NULL,
    comment_type ENUM('note', 'approval', 'rejection', 'modification') DEFAULT 'note',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (request_id) REFERENCES travel_requests(id) ON DELETE CASCADE,
    INDEX idx_request_id (request_id),
    INDEX idx_created_at (created_at)
);

-- Create uploads directory structure
-- Note: This needs to be done at the file system level
-- mkdir -p ../uploads/travel-requests/
-- chmod 755 ../uploads/travel-requests/
