DROP DATABASE IF EXISTS local_infra_db;
CREATE DATABASE local_infra_db CHARACTER SET utf8mb4;
USE local_infra_db;

CREATE TABLE categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(50) NOT NULL UNIQUE
);

CREATE TABLE areas (
    area_id INT AUTO_INCREMENT PRIMARY KEY,
    area_name VARCHAR(80) NOT NULL,
    region VARCHAR(80) NOT NULL,
    location VARCHAR(150) NOT NULL,
    latitude DECIMAL(9,6) NULL,
    longitude DECIMAL(9,6) NULL,
    UNIQUE (area_name, region)
);

CREATE TABLE staff (
    staff_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(100)
);

CREATE TABLE reports (
    report_id INT AUTO_INCREMENT PRIMARY KEY,
    reference_code VARCHAR(20) NOT NULL UNIQUE,
    category_id INT NOT NULL,
    area_id INT NOT NULL,
    description TEXT NOT NULL,
    landmark VARCHAR(150),
    photo_path VARCHAR(255),
    contact_method ENUM('email','phone','whatsapp') NOT NULL,
    contact_detail VARCHAR(100) NOT NULL,
    current_status ENUM('Submitted','Under Review','In Progress','Resolved') NOT NULL DEFAULT 'Submitted',
    date_submitted DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_updated DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(category_id),
    FOREIGN KEY (area_id) REFERENCES areas(area_id)
);

CREATE TABLE status_updates (
    update_id INT AUTO_INCREMENT PRIMARY KEY,
    report_id INT NOT NULL,
    staff_id INT NULL,
    old_status VARCHAR(20),
    new_status VARCHAR(20) NOT NULL,
    resolution_note TEXT,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (report_id) REFERENCES reports(report_id) ON DELETE CASCADE,
    FOREIGN KEY (staff_id) REFERENCES staff(staff_id)
);
