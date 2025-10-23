-- // path: sql/schema.sql
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    phone VARCHAR(20) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    premium_until DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS stations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    latitude DECIMAL(10,7) NOT NULL,
    longitude DECIMAL(10,7) NOT NULL,
    connector VARCHAR(50) NOT NULL,
    status ENUM('available', 'busy', 'offline') NOT NULL DEFAULT 'available',
    price_per_kwh DECIMAL(10,2) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS payments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    amount_etb DECIMAL(10,2) NOT NULL,
    tx_ref VARCHAR(100) NOT NULL UNIQUE,
    status VARCHAR(50) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO users (phone, password_hash, role, premium_until, created_at)
VALUES ('+251900000000', '$2y$12$B6RHNMZnmPr08YIl3bVBbORsRHyzgsuZyN07ax1v4RZwFNiRZ4nla', 'admin', DATE_ADD(NOW(), INTERVAL 365 DAY), NOW())
ON DUPLICATE KEY UPDATE phone = VALUES(phone);

INSERT INTO stations (name, latitude, longitude, connector, status, price_per_kwh) VALUES
('Addis Ababa Bole Station', 8.9778, 38.7993, 'CCS', 'available', 22.50),
('Sarbet Fast Charge', 9.0013, 38.7421, 'Type2', 'busy', 18.75),
('CMC Solar Hub', 9.0405, 38.8137, 'CHAdeMO', 'offline', 16.90)
ON DUPLICATE KEY UPDATE name = VALUES(name);
