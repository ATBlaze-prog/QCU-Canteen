-- ============================================================
-- qcu_canteen.sql  (v2)
-- Run this in phpMyAdmin > Import
-- ============================================================

CREATE DATABASE IF NOT EXISTS qcu_canteen
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE qcu_canteen;

-- ---- Users ----
CREATE TABLE IF NOT EXISTS users (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  full_name       VARCHAR(100)  NOT NULL,
  email           VARCHAR(150)  NOT NULL UNIQUE,
  student_id      VARCHAR(50)   NOT NULL,
  user_type       ENUM('Student','Faculty','Staff','Admin') NOT NULL DEFAULT 'Student',
  password        VARCHAR(255)  NOT NULL,
  role            ENUM('user','admin') NOT NULL DEFAULT 'user',
  profile_picture VARCHAR(255)  DEFAULT NULL,
  created_at      DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ---- Dishes ----
CREATE TABLE IF NOT EXISTS dishes (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  store       VARCHAR(20)   NOT NULL,
  store_name  VARCHAR(100)  NOT NULL,
  dish_name   VARCHAR(100)  NOT NULL,
  category    VARCHAR(50)   NOT NULL DEFAULT 'Meal',
  price       DECIMAL(8,2)  NOT NULL,
  day         ENUM('Monday','Tuesday','Wednesday','Thursday','Friday') NOT NULL,
  description TEXT,
  image       VARCHAR(255)  DEFAULT NULL,
  created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ---- Orders ----
CREATE TABLE IF NOT EXISTS orders (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  order_code  VARCHAR(30)   NOT NULL UNIQUE,
  user_id     INT           DEFAULT NULL,
  customer    VARCHAR(100)  NOT NULL,
  role        VARCHAR(20)   NOT NULL DEFAULT 'Student',
  student_id  VARCHAR(50)   NOT NULL,
  store       VARCHAR(100)  NOT NULL,
  items       TEXT          NOT NULL,
  total       DECIMAL(8,2)  NOT NULL,
  status      ENUM('Pending','Preparing','Ready','Completed') NOT NULL DEFAULT 'Pending',
  created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- ---- Seed admin (password: admin123) ----
-- Hash generated with: password_hash('admin123', PASSWORD_DEFAULT)
INSERT INTO users (full_name, email, student_id, user_type, password, role)
VALUES (
  'Admin',
  'admin@qcu.edu.ph',
  'ADMIN-001',
  'Admin',
  '$2y$10$qaUQdhvDX/8Cmyk0y1Msy.TvcZk6qcauzouQC8W.p4H.yFlBW5oJ6',
  'admin'
) ON DUPLICATE KEY UPDATE password = VALUES(password), role = VALUES(role);