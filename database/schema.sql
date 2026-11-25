CREATE TABLE IF NOT EXISTS counties (
    county_code SMALLINT UNSIGNED NOT NULL PRIMARY KEY,
    county_name VARCHAR(80) NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS subcounties (
    subcounty_code SMALLINT UNSIGNED NOT NULL PRIMARY KEY,
    county_code SMALLINT UNSIGNED NOT NULL,
    subcounty_name VARCHAR(80) NOT NULL,
    UNIQUE KEY uq_subcounty_code_county (subcounty_code, county_code),
    UNIQUE KEY uq_county_subcounty_name (county_code, subcounty_name),
    FOREIGN KEY (county_code) REFERENCES counties(county_code)
);

CREATE TABLE IF NOT EXISTS instanceconfig (
    instance_id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
    county_code SMALLINT UNSIGNED NOT NULL,
    county_name VARCHAR(80) NOT NULL,
    subcounty_code SMALLINT UNSIGNED NOT NULL,
    subcounty_name VARCHAR(80) NOT NULL,
    configured_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (county_code) REFERENCES counties(county_code),
    FOREIGN KEY (subcounty_code, county_code) REFERENCES subcounties(subcounty_code, county_code)
);

CREATE TABLE IF NOT EXISTS categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(50) NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS areas (
    area_id INT AUTO_INCREMENT PRIMARY KEY,
    subcounty_code SMALLINT UNSIGNED NOT NULL,
    area_name VARCHAR(80) NOT NULL,
    region VARCHAR(80) NOT NULL,
    location VARCHAR(150) NOT NULL,
    latitude DECIMAL(9,6) NULL,
    longitude DECIMAL(9,6) NULL,
    UNIQUE KEY uq_area_subcounty_name (subcounty_code, area_name),
    UNIQUE KEY uq_area_name_region (area_name, region),
    FOREIGN KEY (subcounty_code) REFERENCES subcounties(subcounty_code)
);

CREATE TABLE IF NOT EXISTS staff (
    staff_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    subcounty_code SMALLINT UNSIGNED NOT NULL,
    role ENUM('subcounty_admin') NOT NULL DEFAULT 'subcounty_admin',
    approval_status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'approved',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subcounty_code) REFERENCES subcounties(subcounty_code)
);

CREATE TABLE IF NOT EXISTS reports (
    report_id INT AUTO_INCREMENT PRIMARY KEY,
    reference_code VARCHAR(32) NOT NULL UNIQUE,
    category_id INT NOT NULL,
    area_id INT NOT NULL,
    description TEXT NOT NULL,
    landmark VARCHAR(150),
    photo_path VARCHAR(255),
    contact_method ENUM('email','phone','whatsapp') NULL,
    contact_detail VARCHAR(100) NULL,
    current_status ENUM('Submitted','Under Review','In Progress','Resolved') NOT NULL DEFAULT 'Submitted',
    date_submitted DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_updated DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(category_id),
    FOREIGN KEY (area_id) REFERENCES areas(area_id)
);

CREATE TABLE IF NOT EXISTS statusupdates (
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
