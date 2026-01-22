SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

CREATE DATABASE IF NOT EXISTS electricity
CHARACTER SET utf8mb4
COLLATE utf8mb4_general_ci;

USE electricity;

-- =========================
-- CUSTOMER TABLE
-- =========================
DROP TABLE IF EXISTS customer;
CREATE TABLE customer (
    service_number INT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    phone CHAR(10) NOT NULL,
    address TEXT,
    email VARCHAR(100),
    category ENUM('household','commercial','industrial') DEFAULT 'household',
    reg_date DATE DEFAULT CURRENT_DATE
) ENGINE=InnoDB;

-- =========================
-- READINGS TABLE
-- =========================
DROP TABLE IF EXISTS readings;
CREATE TABLE readings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_number INT NOT NULL,
    month TINYINT NOT NULL,
    year SMALLINT NOT NULL,
    reading DECIMAL(10,2) NOT NULL,
    read_date DATE DEFAULT CURRENT_DATE,
    FOREIGN KEY (service_number) REFERENCES customer(service_number)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================
-- BILL TABLE
-- =========================
DROP TABLE IF EXISTS bill;
CREATE TABLE bill (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bill_no INT NOT NULL,
    bill_month TINYINT NOT NULL,
    bill_year SMALLINT NOT NULL,
    units DECIMAL(10,2) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    gst DECIMAL(10,2) DEFAULT 0,
    fine DECIMAL(10,2) DEFAULT 0,
    previous_due DECIMAL(10,2) DEFAULT 0,
    total_amount DECIMAL(10,2) NOT NULL,
    due_date DATE NOT NULL,
    status ENUM('pending','partially_paid','paid') DEFAULT 'pending',
    service_number INT NOT NULL,
    paid_amount DECIMAL(10,2) DEFAULT 0,
    remaining_due DECIMAL(10,2) DEFAULT 0,
    last_payment_date DATE,
    FOREIGN KEY (service_number) REFERENCES customer(service_number)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================
-- PAYMENTS TABLE
-- =========================
DROP TABLE IF EXISTS payments;
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bill_id INT NOT NULL,
    service_number INT NOT NULL,
    amount_paid DECIMAL(10,2) NOT NULL,
    payment_date DATE DEFAULT CURRENT_DATE,
    payment_method VARCHAR(50),
    transaction_id VARCHAR(100),
    status ENUM('completed','failed') DEFAULT 'completed',
    FOREIGN KEY (bill_id) REFERENCES bill(id)
        ON DELETE CASCADE,
    FOREIGN KEY (service_number) REFERENCES customer(service_number)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================
-- PAYMENT HISTORY TABLE
-- =========================
DROP TABLE IF EXISTS payment_history;
CREATE TABLE payment_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_number INT NOT NULL,
    bill_id INT NOT NULL,
    payment_id INT NOT NULL,
    previous_due DECIMAL(10,2),
    paid_amount DECIMAL(10,2),
    remaining_due DECIMAL(10,2),
    payment_date DATE,
    notes TEXT,
    FOREIGN KEY (bill_id) REFERENCES bill(id),
    FOREIGN KEY (payment_id) REFERENCES payments(id)
) ENGINE=InnoDB;

-- =========================
-- MINIMUM CHARGES TABLE
-- =========================
DROP TABLE IF EXISTS minimum_charges;
CREATE TABLE minimum_charges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category ENUM('household','commercial','industrial'),
    min_charge DECIMAL(10,2),
    effective_from DATE
) ENGINE=InnoDB;

-- =========================
-- USERS TABLE (SECURE)
-- =========================
DROP TABLE IF EXISTS users;
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','customer','worker'),
    service_number INT,
    FOREIGN KEY (service_number) REFERENCES customer(service_number)
) ENGINE=InnoDB;

COMMIT;
