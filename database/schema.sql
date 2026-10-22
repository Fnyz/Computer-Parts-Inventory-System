-- Computer Parts Inventory System: database schema (matches the current code)
-- Usage: mysql -u root -p < database/schema.sql
-- The first visit to login.php then offers "Set up admin account".

CREATE DATABASE IF NOT EXISTS inventorymanagementsystem
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE inventorymanagementsystem;

CREATE TABLE IF NOT EXISTS user_account (
  user_id         INT AUTO_INCREMENT PRIMARY KEY,
  user_fname      VARCHAR(100) NOT NULL DEFAULT '',
  user_lname      VARCHAR(100) NOT NULL DEFAULT '',
  user_phone      VARCHAR(30)  NOT NULL DEFAULT '',
  username        VARCHAR(50)  NOT NULL,
  user_pass       VARCHAR(255) NOT NULL,          -- password_hash() output
  userType        VARCHAR(20)  NOT NULL,          -- Admin | Staff | Employee
  user_created_at DATETIME     NOT NULL,
  user_updated_at DATETIME     NOT NULL,
  UNIQUE KEY uq_username (username)
) ENGINE=InnoDB;

-- Column names use the legacy cus_ prefix; cus_lname holds the supplier's address
CREATE TABLE IF NOT EXISTS supplier (
  cus_id         INT AUTO_INCREMENT PRIMARY KEY,
  cus_fname      VARCHAR(150) NOT NULL,
  cus_lname      VARCHAR(255) NOT NULL DEFAULT '',
  cus_phone      VARCHAR(30)  NOT NULL DEFAULT '',
  cus_created_at DATETIME     NOT NULL,
  cus_updated_at DATETIME     NOT NULL
) ENGINE=InnoDB;

-- prod_price / prod_quan are NULL for products waiting to be ordered (used by the order screen)
CREATE TABLE IF NOT EXISTS product (
  prod_id         INT AUTO_INCREMENT PRIMARY KEY,
  prod_name       VARCHAR(150)  NOT NULL,
  prod_image      VARCHAR(255)  NULL,
  prod_price      DECIMAL(12,2) NULL,
  prod_quan       INT           NULL,
  prod_desc       TEXT          NULL,
  prod_created_at DATETIME      NOT NULL,
  prod_updated_at DATETIME      NOT NULL
) ENGINE=InnoDB;

-- One row per product line in a transaction
CREATE TABLE IF NOT EXISTS trans_history (
  trans_id     INT AUTO_INCREMENT PRIMARY KEY,
  prod_id      INT           NOT NULL,
  cus_id       INT           NOT NULL,
  user_id      INT           NOT NULL,
  trans_code   VARCHAR(20)   NOT NULL,
  sub_price    DECIMAL(12,2) NOT NULL DEFAULT 0,
  sub_quantity INT           NOT NULL DEFAULT 0,
  created_date DATETIME      NOT NULL,
  KEY idx_trans_code (trans_code),
  CONSTRAINT fk_th_product  FOREIGN KEY (prod_id) REFERENCES product (prod_id),
  CONSTRAINT fk_th_supplier FOREIGN KEY (cus_id)  REFERENCES supplier (cus_id),
  CONSTRAINT fk_th_user     FOREIGN KEY (user_id) REFERENCES user_account (user_id)
) ENGINE=InnoDB;

-- One row per saved transaction (totals and payment)
CREATE TABLE IF NOT EXISTS transactions (
  transac_id         INT AUTO_INCREMENT PRIMARY KEY,
  transac_code       VARCHAR(20)   NOT NULL,
  transac_total      DECIMAL(12,2) NOT NULL DEFAULT 0,
  transac_quan       INT           NOT NULL DEFAULT 0,
  cus_id             INT           NOT NULL,
  transac_payment    DECIMAL(12,2) NOT NULL DEFAULT 0,
  transac_created_at DATETIME      NOT NULL,
  transac_updated_at DATETIME      NOT NULL,
  KEY idx_transac_code (transac_code),
  CONSTRAINT fk_tr_supplier FOREIGN KEY (cus_id) REFERENCES supplier (cus_id)
) ENGINE=InnoDB;
