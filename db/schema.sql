-- Create database
CREATE DATABASE IF NOT EXISTS minichatapp;
USE minichatapp;

-- Users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    profile_info TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Chats table
CREATE TABLE chats (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user1_id INT NOT NULL,
    user2_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user1_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (user2_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user1_id (user1_id),
    INDEX idx_user2_id (user2_id)
);

-- Messages table
CREATE TABLE messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    chat_id INT NOT NULL,
    sender_id INT NOT NULL,
    content TEXT NOT NULL,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    is_read BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (chat_id) REFERENCES chats(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_chat_timestamp (chat_id, timestamp),
    INDEX idx_sender_id (sender_id)
);

-- Insert some sample data (optional)
INSERT INTO users (username, email, password_hash, profile_info) VALUES
('alice', 'alice@example.com', '$2y$10$YourHashedPasswordHere', 'Hello! I am Alice.'),
('bob', 'bob@example.com', '$2y$10$YourHashedPasswordHere', 'Bob here! Ready to chat.'),
('charlie', 'charlie@example.com', '$2y$10$YourHashedPasswordHere', 'Charlie from the tech team.');