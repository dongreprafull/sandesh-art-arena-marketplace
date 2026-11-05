-- Marketplace portal tables (market.artarenastudio.com)
-- Shares the same database as Personal + Foundation (users, contact_enquiries already exist).

CREATE TABLE IF NOT EXISTS products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(150) NOT NULL,
  description TEXT NULL,
  category VARCHAR(80) NOT NULL DEFAULT 'General',
  price DECIMAL(10,2) NOT NULL,
  image VARCHAR(255) NULL,
  stock_qty INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  buyer_name VARCHAR(120) NOT NULL,
  buyer_email VARCHAR(190) NULL,
  buyer_phone VARCHAR(20) NOT NULL,
  address TEXT NOT NULL,
  city VARCHAR(100) NULL,
  pincode VARCHAR(12) NULL,
  total_amount DECIMAL(10,2) NOT NULL,
  payment_method ENUM('razorpay','upi','cod') NOT NULL,
  payment_status ENUM('pending','paid','failed','cod_pending') NOT NULL DEFAULT 'pending',
  order_status ENUM('placed','confirmed','shipped','delivered','cancelled') NOT NULL DEFAULT 'placed',
  razorpay_order_id VARCHAR(80) NULL,
  razorpay_payment_id VARCHAR(80) NULL,
  upi_proof_url VARCHAR(400) NULL,
  receipt_no VARCHAR(40) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  product_id INT NULL,
  title VARCHAR(150) NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  qty INT NOT NULL,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Singleton settings row: payment config + store text.
CREATE TABLE IF NOT EXISTS marketplace_settings (
  id INT PRIMARY KEY DEFAULT 1,
  about_text TEXT NULL,
  shipping_note TEXT NULL,
  cod_enabled TINYINT(1) NOT NULL DEFAULT 1,
  upi_id VARCHAR(100) NULL,
  upi_qr_image VARCHAR(400) NULL,
  razorpay_key_id VARCHAR(100) NULL,
  razorpay_key_secret VARCHAR(100) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
