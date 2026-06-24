-- ============================================
-- MarketNearMe Database Setup
-- Run this entire script in phpMyAdmin
-- ============================================

-- Create database
CREATE DATABASE IF NOT EXISTS marketnearme;
USE marketnearme;

-- Users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    location VARCHAR(100),
    preferred_currency_id INT DEFAULT 1,
    avatar VARCHAR(255) DEFAULT 'default-avatar.png',
    role ENUM('user', 'admin') DEFAULT 'user',
    status ENUM('active', 'suspended') DEFAULT 'active',
    email_verified TINYINT(1) DEFAULT 0,
    verification_token VARCHAR(255),
    reset_token VARCHAR(255),
    reset_token_expiry DATETIME,
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Categories table
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    icon VARCHAR(50),
    parent_id INT DEFAULT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Currencies table
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

-- Listings table
CREATE TABLE listings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    slug VARCHAR(200) UNIQUE NOT NULL,
    description TEXT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    currency_id INT DEFAULT 1,
    condition_status ENUM('new', 'like_new', 'good', 'fair', 'used') DEFAULT 'used',
    location VARCHAR(100),
    contact_phone VARCHAR(20),
    contact_email VARCHAR(100),
    status ENUM('active', 'sold', 'inactive', 'flagged') DEFAULT 'active',
    views INT DEFAULT 0,
    is_featured TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    FOREIGN KEY (currency_id) REFERENCES currencies(id),
    INDEX idx_status (status),
    INDEX idx_category (category_id),
    INDEX idx_user (user_id),
    INDEX idx_listings_featured (is_featured),
    FULLTEXT idx_search (title, description)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Featured listings table
CREATE TABLE featured_listings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    listing_id INT NOT NULL UNIQUE,
    featured_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    expiry_date DATETIME NULL,
    featured_by INT NOT NULL,
    notes TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE,
    FOREIGN KEY (featured_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Listing images table
CREATE TABLE listing_images (
    id INT PRIMARY KEY AUTO_INCREMENT,
    listing_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    is_primary TINYINT(1) DEFAULT 0,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Messages table
CREATE TABLE messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    listing_id INT,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Favorites table
CREATE TABLE favorites (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    listing_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_favorite (user_id, listing_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Reviews table
CREATE TABLE reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    reviewer_id INT NOT NULL,
    reviewed_user_id INT NOT NULL,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Reports table
CREATE TABLE reports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    reporter_id INT NOT NULL,
    listing_id INT NOT NULL,
    reason VARCHAR(255) NOT NULL,
    description TEXT,
    status ENUM('pending', 'reviewed', 'resolved') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Security logs table
CREATE TABLE security_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sessions table for secure session management
CREATE TABLE sessions (
    id VARCHAR(255) PRIMARY KEY,
    user_id INT NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    data TEXT,
    last_activity INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add indexes for featured_listings
CREATE INDEX idx_featured_active ON featured_listings(is_active, expiry_date);

-- Add foreign key for users.preferred_currency_id
ALTER TABLE users 
ADD FOREIGN KEY (preferred_currency_id) REFERENCES currencies(id);

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

-- Insert default admin user (password: Admin@123)
INSERT INTO users (username, email, password, full_name, role, email_verified, status, preferred_currency_id) 
VALUES ('admin', 'admin@marketnearme.com', '$2y$12$LJ3m4ys3Gql.ZGSuFPAM6u2QpVNMNTFqBSkTlGVOgMQjjXZ6QWhNe', 'Administrator', 'admin', 1, 'active', 1);

-- Insert sample categories
INSERT INTO categories (name, slug, description, icon) VALUES
('Electronics', 'electronics', 'Phones, computers, gadgets and more', 'fa-laptop'),
('Vehicles', 'vehicles', 'Cars, motorcycles, and other vehicles', 'fa-car'),
('Real Estate', 'real-estate', 'Houses, apartments, land for sale or rent', 'fa-home'),
('Fashion', 'fashion', 'Clothing, shoes, accessories', 'fa-tshirt'),
('Home & Garden', 'home-garden', 'Furniture, appliances, garden tools', 'fa-couch'),
('Jobs', 'jobs', 'Find job opportunities near you', 'fa-briefcase'),
('Services', 'services', 'Local services offered', 'fa-tools'),
('Sports & Outdoors', 'sports-outdoors', 'Sports equipment and outdoor gear', 'fa-futbol');