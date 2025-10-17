-- Tạo database nếu chưa có
CREATE DATABASE IF NOT EXISTS buildpc_db DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE buildpc_db;

-- ==============================
-- BẢNG SẢN PHẨM (products)
-- ==============================
DROP TABLE IF EXISTS products;
CREATE TABLE products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  category VARCHAR(100) NOT NULL,
  price DECIMAL(12,2) NOT NULL DEFAULT 0,
  description TEXT,
  image VARCHAR(255),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ==============================
-- BẢNG CẤU HÌNH & LIÊN KẾT
-- ==============================
DROP TABLE IF EXISTS configurations;
CREATE TABLE configurations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) DEFAULT 'My config',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

DROP TABLE IF EXISTS configuration_items;
CREATE TABLE configuration_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  configuration_id INT NOT NULL,
  product_id INT NOT NULL,
  FOREIGN KEY (configuration_id) REFERENCES configurations(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- ==============================
-- BẢNG NGƯỜI DÙNG (LOGIN)
-- ==============================
CREATE TABLE IF NOT EXISTS users (
  user_id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  email VARCHAR(100),
  password_hash VARCHAR(255) NOT NULL,
  full_name VARCHAR(100),
  role ENUM('admin','customer') DEFAULT 'customer',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO users (username, email, password_hash, full_name, role)
VALUES 
('admin', 'admin@buildpc.vn', '123456', 'Quản trị viên', 'admin'),
('user1', 'user1@gmail.com', '123456', 'Nguyễn Văn A', 'customer');

INSERT INTO products (name, category, price, description) VALUES
('Intel Core i5-12400F', 'CPU', 3700000, '6 cores, 12 threads'),
('AMD Ryzen 5 5600X', 'CPU', 4500000, '6 cores, 12 threads'),
('NVIDIA RTX 3060', 'GPU', 8000000, '12GB GDDR6'),
('Corsair Vengeance 16GB (2x8) 3200MHz', 'RAM', 1200000, 'DDR4 Kit'),
('ASUS PRIME B660M-A', 'MAIN', 2100000, 'Socket LGA1700 mATX'),
('Corsair CV650 650W', 'PSU', 900000, '80+ Bronze'),
('Samsung 970 EVO Plus 500GB', 'SSD', 1800000, 'NVMe M.2');
