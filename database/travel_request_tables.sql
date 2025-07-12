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

-- Approvers table (optional - for managing approver list)
CREATE TABLE IF NOT EXISTS approvers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    approver_code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    title VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    department VARCHAR(100),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert sample approvers
INSERT INTO approvers (approver_code, name, title, email, department) VALUES
('manager1', 'John Smith', 'Department Manager', 'john.smith@mowaa.com', 'Operations'),
('manager2', 'Sarah Johnson', 'Finance Manager', 'sarah.johnson@mowaa.com', 'Finance'),
('manager3', 'Michael Brown', 'Operations Manager', 'michael.brown@mowaa.com', 'Operations'),
('director1', 'Lisa Davis', 'Regional Director', 'lisa.davis@mowaa.com', 'Management');

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
