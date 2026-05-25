-- SQL Migration: Create google_reviews table
CREATE TABLE IF NOT EXISTS google_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    author_name VARCHAR(100) NOT NULL,
    author_photo VARCHAR(255) DEFAULT NULL,
    rating INT NOT NULL DEFAULT 5,
    review_text TEXT DEFAULT NULL,
    review_time VARCHAR(100) DEFAULT NULL,
    is_visible TINYINT(1) DEFAULT 1,
    is_manual TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
