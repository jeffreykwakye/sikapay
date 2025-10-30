-- Migration: create_staff_files_table

CREATE TABLE IF NOT EXISTS staff_files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    tenant_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,        -- Original name of the uploaded file
    file_path VARCHAR(512) NOT NULL,        -- Secure storage path (e.g., S3 URL or local path)
    file_type ENUM('Contract', 'Certification', 'ID Card', 'Tax Document', 'Other') NOT NULL,
    file_description VARCHAR(255) NULL,          -- Allows users to describe 'Other' files (default NULL)
    uploaded_by_user_id INT NULL,           -- User who performed the upload (e.g., Admin)
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=INNODB;