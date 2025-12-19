-- Fix users table to include email and incharge_role columns
-- Run this in phpMyAdmin to fix the table structure

USE academic_auditt;

-- Drop existing table if you want a clean start (WARNING: This deletes all user data!)
-- DROP TABLE IF EXISTS users;

-- Option 1: ALTER existing table to add missing columns
ALTER TABLE users 
ADD COLUMN email VARCHAR(100) UNIQUE NULL AFTER username,
ADD COLUMN incharge_role VARCHAR(100) NULL AFTER role;

-- Option 2: If above fails, create new table with correct structure
-- Uncomment below if you need to recreate the table:
/*
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) DEFAULT 'teacher',
    incharge_role VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
*/
