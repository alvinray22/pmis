CREATE DATABASE IF NOT EXISTS property_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE property_management;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS properties (
  id INT AUTO_INCREMENT PRIMARY KEY,
  property_no VARCHAR(100) NOT NULL UNIQUE,
  description VARCHAR(255) NOT NULL,
  date_acquired DATE NULL,
  accountable_person VARCHAR(150) NULL,
  location VARCHAR(150) NULL,
  status VARCHAR(50) NOT NULL DEFAULT 'Serviceable',
  cost DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO users(username,password)
SELECT 'admin', '$2y$10$Q0xq6d9K5n6Q3gQnH0gQ8e6x3f3fW7aYQ1kP0u3f9Yw5vR4k9Vf2e'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE username='admin');

INSERT INTO properties(property_no,description,date_acquired,accountable_person,location,status,cost) VALUES
('PROP-0001','Sample Office Computer','2026-01-15','Sample Accountable Person','Registrar Office','Serviceable',25000.00),
('PROP-0002','Sample Office Chair','2026-02-10','Sample Accountable Person','Registrar Office','Good/Issued',4500.00)
ON DUPLICATE KEY UPDATE property_no=VALUES(property_no);
