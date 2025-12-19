CREATE TABLE IF NOT EXISTS requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    head_id INT NOT NULL,
    requirement VARCHAR(255) NOT NULL,
    role_id INT NULL,
    role_name VARCHAR(191) NOT NULL,
    semester VARCHAR(10) NOT NULL,
    year VARCHAR(10) DEFAULT '2025',
    status VARCHAR(50) DEFAULT 'pending',
    document_path VARCHAR(255) NULL,
    incharge_id INT NULL,
    uploaded_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (head_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES role_templates(id) ON DELETE SET NULL,
    FOREIGN KEY (incharge_id) REFERENCES users(id) ON DELETE SET NULL
);
