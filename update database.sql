-- Run this in phpMyAdmin to update your database

-- Create currencies table
CREATE TABLE currencies (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(3) UNIQUE NOT NULL,
    name VARCHAR(50) NOT NULL,
    symbol VARCHAR(10) NOT NULL,
    symbol_position ENUM('before', 'after') DEFAULT 'before',
    decimal_places INT DEFAULT 2,
    decimal_separator VARCHAR(1) DEFAULT '.',
    thousands_separator VARCHAR(1) DEFAULT ',',
    exchange_rate DECIMAL(10,4) DEFAULT 1.0000,
    is_default TINYINT(1) DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert common currencies
INSERT INTO currencies (code, name, symbol, symbol_position, decimal_places, decimal_separator, thousands_separator, is_default) VALUES
('USD', 'US Dollar', '$', 'before', 2, '.', ',', 1),
('EUR', 'Euro', '€', 'before', 2, ',', '.', 0),
('GBP', 'British Pound', '£', 'before', 2, '.', ',', 0),
('KES', 'Kenyan Shilling', 'KSh', 'before', 2, '.', ',', 0),
('NGN', 'Nigerian Naira', '₦', 'before', 2, '.', ',', 0),
('ZAR', 'South African Rand', 'R', 'before', 2, '.', ',', 0),
('GHS', 'Ghanaian Cedi', 'GH₵', 'before', 2, '.', ',', 0),
('INR', 'Indian Rupee', '₹', 'before', 2, '.', ',', 0),
('AUD', 'Australian Dollar', 'A$', 'before', 2, '.', ',', 0),
('CAD', 'Canadian Dollar', 'C$', 'before', 2, '.', ',', 0),
('JPY', 'Japanese Yen', '¥', 'before', 0, '.', ',', 0),
('CNY', 'Chinese Yuan', '¥', 'before', 2, '.', ',', 0),
('AED', 'UAE Dirham', 'د.إ', 'before', 2, '.', ',', 0),
('SAR', 'Saudi Riyal', '﷼', 'before', 2, '.', ',', 0);

-- Add currency_id to listings table
ALTER TABLE listings 
ADD COLUMN currency_id INT DEFAULT 1 AFTER price,
ADD FOREIGN KEY (currency_id) REFERENCES currencies(id);

-- Add default currency to users table
ALTER TABLE users 
ADD COLUMN preferred_currency_id INT DEFAULT 1 AFTER location,
ADD FOREIGN KEY (preferred_currency_id) REFERENCES currencies(id);