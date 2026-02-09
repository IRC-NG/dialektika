-- Database for News, Opinion, and Knowledge Portal

CREATE DATABASE IF NOT EXISTS portal_berita;
USE portal_berita;

-- Table for Admin Users
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Default Admin User (password: admin123)
-- Ideally this should be hashed in PHP, but for initial setup we'll assume the PHP code handles hashing verify or insert raw for testing (we will perform hashing in PHP script)
INSERT INTO users (username, password) VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'); 
-- Hash for 'password' using user_hash logic

-- Table for Categories (Berita, Opini, Pengetahuan)
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    slug VARCHAR(50) NOT NULL UNIQUE
);

INSERT INTO categories (name, slug) VALUES 
('Berita', 'berita'),
('Opini', 'opini'),
('Pengetahuan', 'pengetahuan');

-- Table for Posts
CREATE TABLE IF NOT EXISTS posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    content TEXT NOT NULL,
    category_id INT,
    image VARCHAR(255) DEFAULT NULL,
    file_attachment VARCHAR(255) DEFAULT NULL,
    views INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Table for Site Settings (Ads, Header, Running Text)
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    site_title VARCHAR(100) DEFAULT 'Portal Pintar',
    tagline VARCHAR(255) DEFAULT 'Berita, Opini, dan Wawasan',
    header_image VARCHAR(255) DEFAULT 'default_header.jpg',
    running_text TEXT,
    ad_sidebar TEXT,
    ad_footer TEXT,
    contact_email VARCHAR(100),
    about_text TEXT
);

INSERT INTO settings (running_text, ad_sidebar, ad_footer, about_text) VALUES 
('Selamat datang di portal berita dan opini terkini. Simak update terbaru setiap hari!', '<div class="ad-box">Space Iklan Sidebar (300x250)</div>', '<div class="ad-box">Space Iklan Footer (728x90)</div>', 'Website ini didedikasikan untuk berbagi pengetahuan dan opini.');
