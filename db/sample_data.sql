-- Sample Data for MiniChatApp
-- Password for all users: password123

USE minichatapp;

-- Clear existing data (optional - remove if you want to keep existing data)
-- SET FOREIGN_KEY_CHECKS = 0;
-- TRUNCATE TABLE messages;
-- TRUNCATE TABLE chats;
-- TRUNCATE TABLE users;
-- SET FOREIGN_KEY_CHECKS = 1;

-- Insert sample users
-- All passwords are: password123
INSERT INTO users (username, email, password_hash, profile_info) VALUES
('john_doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Software developer passionate about creating innovative solutions. Coffee enthusiast ☕'),
('sarah_smith', 'sarah@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Product designer with 5 years of experience. Love minimalist designs 🎨'),
('mike_wilson', 'mike@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Marketing specialist and digital nomad. Always exploring new places 🌍'),
('emma_brown', 'emma@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Data analyst by day, photographer by night 📸'),
('alex_chen', 'alex@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Full-stack developer | Open source contributor | Tech blogger 💻');

-- Create chats between users
INSERT INTO chats (user1_id, user2_id, created_at) VALUES
(1, 2, '2025-12-17 10:00:00'),  -- john_doe <-> sarah_smith
(1, 3, '2025-12-17 11:30:00'),  -- john_doe <-> mike_wilson
(2, 4, '2025-12-17 14:00:00'),  -- sarah_smith <-> emma_brown
(3, 5, '2025-12-17 15:30:00'),  -- mike_wilson <-> alex_chen
(1, 5, '2025-12-18 09:00:00');  -- john_doe <-> alex_chen

-- Insert sample messages

-- Chat 1: john_doe (1) and sarah_smith (2)
INSERT INTO messages (chat_id, sender_id, content, timestamp) VALUES
(1, 1, 'Hey Sarah! How are you doing?', '2025-12-17 10:05:00'),
(1, 2, 'Hi John! I am doing great, thanks! Just finished a new design project.', '2025-12-17 10:07:00'),
(1, 1, 'That sounds awesome! Would love to see it sometime.', '2025-12-17 10:10:00'),
(1, 2, 'Sure! I will share it with you tomorrow. How is your coding project going?', '2025-12-17 10:12:00'),
(1, 1, 'Going well! Just deployed a new feature today. Pretty excited about it!', '2025-12-17 10:15:00'),
(1, 2, 'Congrats! 🎉', '2025-12-17 10:16:00');

-- Chat 2: john_doe (1) and mike_wilson (3)
INSERT INTO messages (chat_id, sender_id, content, timestamp) VALUES
(2, 3, 'John! I have a marketing idea for your new app.', '2025-12-17 11:35:00'),
(2, 1, 'Really? Tell me more!', '2025-12-17 11:40:00'),
(2, 3, 'We could run a social media campaign targeting developers. I have some connections.', '2025-12-17 11:42:00'),
(2, 1, 'That sounds interesting. Let us schedule a call to discuss this further.', '2025-12-17 11:45:00'),
(2, 3, 'Perfect! How about tomorrow at 3 PM?', '2025-12-17 11:47:00'),
(2, 1, 'Works for me. See you then!', '2025-12-17 11:50:00');

-- Chat 3: sarah_smith (2) and emma_brown (4)
INSERT INTO messages (chat_id, sender_id, content, timestamp) VALUES
(3, 2, 'Emma! Did you see the new design trends for 2025?', '2025-12-17 14:05:00'),
(3, 4, 'Yes! The minimalist approach is really taking over.', '2025-12-17 14:08:00'),
(3, 2, 'Exactly! I have been incorporating more white space in my designs.', '2025-12-17 14:10:00'),
(3, 4, 'By the way, I took some photos yesterday. Want to see them?', '2025-12-17 14:12:00'),
(3, 2, 'Absolutely! I would love to see your work.', '2025-12-17 14:15:00'),
(3, 4, 'I will send you the link tonight! 📷', '2025-12-17 14:17:00');

-- Chat 4: mike_wilson (3) and alex_chen (5)
INSERT INTO messages (chat_id, sender_id, content, timestamp) VALUES
(4, 3, 'Alex, are you attending the tech conference next month?', '2025-12-17 15:35:00'),
(4, 5, 'Planning to! Are you going as well?', '2025-12-17 15:40:00'),
(4, 3, 'Yes! It would be great to meet up there.', '2025-12-17 15:42:00'),
(4, 5, 'Definitely! Let me know which talks you are interested in.', '2025-12-17 15:45:00'),
(4, 3, 'I am interested in the AI and Machine Learning sessions.', '2025-12-17 15:48:00'),
(4, 5, 'Same here! We should go together.', '2025-12-17 15:50:00');

-- Chat 5: john_doe (1) and alex_chen (5)
INSERT INTO messages (chat_id, sender_id, content, timestamp) VALUES
(5, 1, 'Hey Alex! Saw your latest blog post. Really insightful!', '2025-12-18 09:05:00'),
(5, 5, 'Thanks John! Glad you liked it. What did you think about the React tips?', '2025-12-18 09:10:00'),
(5, 1, 'Super helpful! I implemented the custom hooks pattern you mentioned.', '2025-12-18 09:15:00'),
(5, 5, 'Awesome! Let me know how it works out for you.', '2025-12-18 09:18:00'),
(5, 1, 'Will do! Maybe we can collaborate on an open source project?', '2025-12-18 09:20:00'),
(5, 5, 'I am in! Let us brainstorm some ideas this week.', '2025-12-18 09:22:00');

-- Display summary
SELECT 
    'Data inserted successfully!' as status,
    (SELECT COUNT(*) FROM users) as total_users,
    (SELECT COUNT(*) FROM chats) as total_chats,
    (SELECT COUNT(*) FROM messages) as total_messages;
