-- database/schema.sql
CREATE DATABASE IF NOT EXISTS pap CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pap;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  role ENUM('user','admin') NOT NULL DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cards (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  name VARCHAR(100) NOT NULL,        -- ex: "Cartão Visa"
  last4 CHAR(4) NULL,                -- últimos 4 números
  limit_amount DECIMAL(10,2) DEFAULT 0,
  balance DECIMAL(10,2) DEFAULT 0,   -- usado para gerir saldo/consumo
  color VARCHAR(20) DEFAULT 'purple', -- cor do cartão
  active TINYINT(1) DEFAULT 1,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS transactions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  card_id INT UNSIGNED NULL,
  amount DECIMAL(10,2) NOT NULL,
  description VARCHAR(255),
  category VARCHAR(100) DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (card_id) REFERENCES cards(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- substitui user_id por um id válido (ex.: 1)
INSERT INTO cards (user_id, name, last4, limit_amount, balance, color) VALUES
(1, 'Visa Principal', '1234', 1500.00, 300.00, 'purple'),
(1, 'Mastercard Secundário', '9876', 1000.00, 50.00, 'blue');

INSERT INTO transactions (user_id, card_id, amount, description, category, created_at) VALUES
(1, 1, 45.60, 'Café e snack', 'Alimentação', NOW() - INTERVAL 2 DAY),
(1, 1, 120.00, 'Supermercado', 'Compras', NOW() - INTERVAL 5 DAY),
(1, 2, 12.50, 'Uber', 'Transporte', NOW() - INTERVAL 1 DAY);

SELECT COALESCE(SUM(amount),0) AS total_month
FROM transactions
WHERE user_id = :uid
  AND created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01');

SELECT COUNT(*) AS cnt
FROM transactions
WHERE user_id = :uid AND created_at >= NOW() - INTERVAL 30 DAY;

SELECT t.*, c.name AS card_name, c.last4
FROM transactions t
LEFT JOIN cards c ON c.id = t.card_id
WHERE t.user_id = :uid
ORDER BY t.created_at DESC
LIMIT 8;

SELECT id, name, last4, limit_amount, balance, color, active
FROM cards
WHERE user_id = :uid;
