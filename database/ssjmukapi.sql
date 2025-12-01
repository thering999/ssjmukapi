-- IMPORTANT: Ensure this file is saved as UTF-8 (without BOM).
-- This header forces the MySQL connection to use utf8mb4 so Thai characters import correctly.
SET NAMES 'utf8mb4';
SET @@session.character_set_client = 'utf8mb4';
SET @@session.character_set_results = 'utf8mb4';
SET @@session.character_set_connection = 'utf8mb4';
SET @@session.collation_connection = 'utf8mb4_unicode_ci';

-- Disable foreign key checks to allow dropping tables
SET FOREIGN_KEY_CHECKS=0;

-- --------------------------------------------------------
-- 1. Schema Migration (Create Tables)
-- --------------------------------------------------------

-- Facilities Table
DROP TABLE IF EXISTS facilities;
CREATE TABLE facilities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL,
    name_th VARCHAR(255) NOT NULL,
    type VARCHAR(50),
    province_code VARCHAR(10),
    district_code VARCHAR(10),
    lat DECIMAL(10, 8),
    lng DECIMAL(11, 8),
    phone VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_code (code),
    INDEX idx_province_district (province_code, district_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Announcements Table
DROP TABLE IF EXISTS announcements;
CREATE TABLE announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    body TEXT,
    published TINYINT(1) DEFAULT 0,
    published_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_published (published),
    INDEX idx_published_at (published_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Contacts Table
DROP TABLE IF EXISTS contacts;
CREATE TABLE contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name_th VARCHAR(255) NOT NULL,
    phone VARCHAR(50),
    email VARCHAR(100),
    url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FULLTEXT idx_search (name_th, email, phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Disease Cases Table
DROP TABLE IF EXISTS disease_cases;
CREATE TABLE disease_cases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_date DATE NOT NULL,
    icd10 VARCHAR(20) NOT NULL,
    province_code VARCHAR(10),
    district_code VARCHAR(10),
    cases INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_report_date (report_date),
    INDEX idx_icd10 (icd10)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Vaccinations Table
DROP TABLE IF EXISTS vaccinations;
CREATE TABLE vaccinations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_date DATE NOT NULL,
    vaccine_code VARCHAR(50) NOT NULL,
    province_code VARCHAR(10),
    dose1 INT DEFAULT 0,
    dose2 INT DEFAULT 0,
    booster INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_report_date (report_date),
    INDEX idx_vaccine_code (vaccine_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- API Keys Table (Legacy/Admin)
DROP TABLE IF EXISTS api_keys;
CREATE TABLE api_keys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    api_key VARCHAR(100) NOT NULL UNIQUE,
    name VARCHAR(100),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Users Table (For JWT Auth)
DROP TABLE IF EXISTS users;
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(20) DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 2. Seed Data
-- --------------------------------------------------------

-- Seed Facilities
INSERT INTO facilities (code, name_th, type, province_code, district_code, lat, lng, phone) VALUES 
('H001','โรงพยาบาลจังหวัด','HOSPITAL','34','3401',15.2391000,104.8487000,'045-123456'),
('C101','คลินิกสุขภาพชุมชน','CLINIC','34','3401',NULL,NULL,'045-234567'),
('H002','โรงพยาบาลชุมชน','HOSPITAL','34','3402',15.3125000,104.7234000,'045-345678'),
('C102','สถานีอนามัย','PHC','34','3402',NULL,NULL,'045-456789'),
('H003','โรงพยาบาลส่งเสริมสุขภาพ','PHC','34','3403',15.4562000,104.6543000,'045-567890');

-- Seed Announcements
INSERT INTO announcements (title, body, published, published_at) VALUES 
('แจ้งเตือน PM2.5','ประชาชนควรสวมหน้ากากและติดตามประกาศ',1,NOW()),
('กำหนดการรณรงค์ฉีดวัคซีน','ขอเชิญประชาชนในพื้นที่ร่วมกิจกรรม',1,NOW()),
('ข่าวประชาสัมพันธ์ โครงการคัดกรองโรคไตจังหวัด','จัดขึ้นระหว่างวันที่ 1-15 พฤศจิกายน 2568',1,NOW()),
('ประกาศปิดปรับปรุงระบบ','ระบบจะปิดปรับปรุงชั่วคราววันที่ 20 พฤศจิกายน',0,NULL);

-- Seed Contacts
INSERT INTO contacts (name_th, phone, email, url) VALUES 
('ศูนย์บริการประชาชน','045-111000','info@example.go.th','https://example.go.th'),
('ศูนย์ข้อมูลข่าวสาร','045-222000','opendata@example.go.th','https://data.example.go.th'),
('แผนกฉุกเฉิน','045-333000','emergency@example.go.th',NULL);

-- Seed Disease Cases (sample weekly reports for Oct 2025)
INSERT INTO disease_cases (report_date, icd10, province_code, district_code, cases) VALUES
-- Influenza (J10)
('2025-10-01','J10','34','3401',12),
('2025-10-01','J10','34','3402',8),
('2025-10-01','J10','34','3403',5),
('2025-10-08','J10','34','3401',15),
('2025-10-08','J10','34','3402',10),
('2025-10-08','J10','34','3403',7),
-- Dengue fever (A90)
('2025-10-01','A90','34','3401',3),
('2025-10-01','A90','34','3402',5),
('2025-10-08','A90','34','3401',2),
('2025-10-08','A90','34','3402',6);

-- Seed Vaccinations (sample weekly reports for Oct 2025)
INSERT INTO vaccinations (report_date, vaccine_code, province_code, dose1, dose2, booster) VALUES
-- COVID-19 vaccine
('2025-10-01','COVID19','34',150,120,80),
('2025-10-08','COVID19','34',145,125,85),
-- Influenza vaccine
('2025-10-01','FLU','34',200,0,0),
('2025-10-08','FLU','34',180,0,0);

-- Seed API Keys
INSERT INTO api_keys (api_key, name, is_active) VALUES
('ssjmuk_2025','Development Admin Key',1);

-- Seed Users (Default Admin: admin / password)
INSERT INTO users (username, password_hash, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS=1;
