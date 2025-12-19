CREATE DATABASE IF NOT EXISTS academic_auditt CHARACTER SET utf8mb4 COLLATE
    utf8mb4_unicode_ci;
USE academic_auditt;
DROP TABLE IF EXISTS reports;
DROP TABLE IF EXISTS users;
CREATE TABLE users(id INT AUTO_INCREMENT PRIMARY KEY,
                   username VARCHAR(100) UNIQUE NOT NULL,
                   password VARCHAR(255) NOT NULL,
                   role ENUM('teacher', 'head') NOT NULL,
                   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE
reports(id INT AUTO_INCREMENT PRIMARY KEY, teacher_id INT NOT NULL,
        title VARCHAR(255) NOT NULL, filename VARCHAR(255) NOT NULL,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        remarks TEXT NULL, submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        reviewed_at TIMESTAMP NULL DEFAULT NULL,
        FOREIGN KEY(teacher_id) REFERENCES users(id) ON DELETE CASCADE);
INSERT INTO users(username, password, role)
    VALUES('Rakhimam', 'teacher123', 'teacher'),
    ('shelkemam', 'teacher123', 'teacher'), ('Kapremam', 'head123', 'head');
