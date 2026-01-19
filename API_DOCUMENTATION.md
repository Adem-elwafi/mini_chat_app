# MiniChatApp - Detailed Technical Report

## Project Overview
**MiniChatApp** is a real-time chat application built with PHP (backend), vanilla JavaScript (frontend), Bootstrap (UI), and MySQL (database). The application uses a session-based authentication model with RESTful API endpoints for chat operations.

---

## 1. Architecture Overview

### Technology Stack
- **Backend**: PHP 7+ with PDO (Database abstraction)
- **Frontend**: HTML5, CSS3, vanilla JavaScript
- **Database**: MySQL 5.7+
- **UI Framework**: Bootstrap 5.3.0
- **Authentication**: PHP Sessions with password hashing (password_hash/password_verify)

### Project Structure
```
mini_chat_app/
├── backend/
│   ├── auth/              (Authentication API)
│   │   ├── login.php
│   │   ├── register.php
│   │   ├── logout.php
│   │   └── updateProfile.php
│   ├── chat/              (Chat API)
│   │   ├── fetch_messages.php
│   │   ├── fetch_users.php
│   │   ├── send_message.php
│   │   └── start_chat.php
│   └── config/
│       └── db.php         (Database singleton & helpers)
├── public/
│   ├── index.php          (Login/Register page)
│   ├── chat.php           (Main chat interface)
│   ├── profile.php        (User profile page)
│   ├── css/
│   │   └── style.css
│   └── js/
│       └── chat.js
├── db/
│   ├── schema.sql         (Database structure)
│   └── sample_data.sql    (Sample data)
└── README.md
```

---

## 2. Database Schema

### Tables Structure

#### **users**
Stores user account information and authentication data.
```sql
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    profile_info TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

#### **chats**
Maintains one-to-one chat conversations between users.
```sql
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
```

#### **messages**
Stores all messages sent between users in a chat.
```sql
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
```

### Database Connection
- **Host**: localhost
- **Database**: minichatapp
- **User**: root
- **Password**: (empty)
- **Charset**: utf8mb4

---

## 3. API Documentation

### Base Configuration (`backend/config/db.php`)

**Database Singleton Class**
```php
Database::getInstance()
```
Returns the PDO connection instance. Implements singleton pattern for a single connection throughout the app.

**Helper Functions**
- `isLoggedIn()` - Checks if user is authenticated
- `requireLogin()` - Enforces authentication (exits with 401 if not logged in)
- `getUserID()` - Returns current logged-in user's ID from session
- `jsonResponse($data, $status = 200)` - Sends JSON response with HTTP status code

---

## 4. Authentication API

All authentication endpoints accept JSON request bodies and return JSON responses.

### 4.1 User Registration
**Endpoint**: `POST /backend/auth/register.php`

**Request Body**
```json
{
    "username": "john_doe",
    "email": "john@example.com",
    "password": "securePassword123",
    "profile_info": "Hey! I'm John."
}
```

**Validation Rules**
- Username: Required, must be unique
- Email: Required, must be valid format, must be unique
- Password: Required, minimum 6 characters
- Profile Info: Optional

**Success Response** (200)
```json
{
    "success": true,
    "message": "Registration successful",
    "user": {
        "id": 1,
        "username": "john_doe",
        "email": "john@example.com",
        "profile_info": "Hey! I'm John."
    }
}
```

**Error Response** (400/500)
```json
{
    "errors": ["Username is required", "Email is required"],
    "error": "Email already registered"
}
```

**Side Effects**
- User session is automatically created (`$_SESSION['user_id']`, `$_SESSION['username']`, `$_SESSION['email']`)
- Password is hashed using `PASSWORD_DEFAULT` algorithm (bcrypt)

---

### 4.2 User Login
**Endpoint**: `POST /backend/auth/login.php`

**Request Body**
```json
{
    "email": "john@example.com",
    "password": "securePassword123"
}
```

**Validation Rules**
- Email: Required
- Password: Required
- Credentials must match database records

**Success Response** (200)
```json
{
    "success": true,
    "message": "Login successful",
    "user": {
        "id": 1,
        "username": "john_doe",
        "email": "john@example.com",
        "profile_info": "Hey! I'm John."
    }
}
```

**Error Response** (401)
```json
{
    "error": "Invalid email or password"
}
```

**Side Effects**
- User session is created with authenticated user data
- Client stores user data in `localStorage` as JSON

---

### 4.3 User Logout
**Endpoint**: `POST /backend/auth/logout.php`

**Request Body**
```json
{}
```
(Empty JSON required for POST)

**Headers**
```
Accept: application/json
```

**Success Response** (200)
```json
{
    "success": true,
    "message": "Logged out successfully"
}
```

**Side Effects**
- Session is destroyed: `$_SESSION = []`
- Session cookie is deleted
- Session is terminated with `session_destroy()`
- Client redirects to `index.php`
- Client clears `localStorage` (user data removed)

---

### 4.4 Update User Profile
**Endpoint**: `POST /backend/auth/updateProfile.php`

**Authentication**: ✅ Required (checks `$_SESSION['user_id']`)

**Request Body**
```json
{
    "username": "john_doe_updated",
    "email": "newemail@example.com",
    "profile_info": "Updated profile info"
}
```

**Validation Rules**
- Username: Required
- Email: Required, must be valid format
- Email must not be taken by another user (current user can keep their email)
- Profile Info: Optional

**Success Response** (200)
```json
{
    "success": true,
    "message": "Profile updated successfully",
    "user": {
        "id": 1,
        "username": "john_doe_updated",
        "email": "newemail@example.com",
        "profile_info": "Updated profile info"
    }
}
```

**Error Response** (400/500)
```json
{
    "success": false,
    "message": "Invalid email format"
}
```

**Side Effects**
- Updates user record in database with `updated_at = NOW()`
- Session is updated with new username and email
- `localStorage` should be updated on client side

**Note**: This endpoint has an inconsistency - it requires `../config/database.php` instead of using the singleton from `db.php`. This is a bug and should be fixed.

---

## 5. Chat API

### 5.1 Fetch All Users
**Endpoint**: `GET /backend/chat/fetch_users.php`

**Authentication**: ✅ Required

**Query Parameters**: None

**Success Response** (200)
```json
{
    "success": true,
    "users": [
        {
            "id": 2,
            "username": "alice",
            "email": "alice@example.com",
            "profile_info": "Hello! I am Alice.",
            "created_at": "2026-01-15 10:30:00"
        },
        {
            "id": 3,
            "username": "bob",
            "email": "bob@example.com",
            "profile_info": "Bob here! Ready to chat.",
            "created_at": "2026-01-16 14:20:00"
        }
    ]
}
```

**Details**
- Returns all users except the current authenticated user
- Results are ordered alphabetically by username
- Used to populate the user list in the chat sidebar

---

### 5.2 Start Chat / Get Chat ID
**Endpoint**: `POST /backend/chat/start_chat.php`

**Authentication**: ✅ Required

**Request Body**
```json
{
    "user_id": 2
}
```

**Validation Rules**
- user_id: Required, must exist in database, cannot be the current user's ID

**Success Response** (200)
```json
{
    "success": true,
    "chat": {
        "id": 5,
        "user1_id": 1,
        "user2_id": 2,
        "user1_name": "john_doe",
        "user2_name": "alice",
        "created_at": "2026-01-18 09:15:00"
    }
}
```

**Error Response** (400/404)
```json
{
    "error": "User not found"
}
```

**Logic**
- If chat already exists between the two users, returns existing chat ID
- If not, creates a new chat with consistent ordering (smaller user_id as user1_id)
- This ensures only one chat exists between any two users

---

### 5.3 Send Message
**Endpoint**: `POST /backend/chat/send_message.php`

**Authentication**: ✅ Required

**Request Body**
```json
{
    "chat_id": 5,
    "content": "Hello! How are you?"
}
```

**Validation Rules**
- chat_id: Required
- content: Required, must not be empty
- User must have access to this chat (verified via verification query)

**Success Response** (200)
```json
{
    "success": true,
    "message": "Message sent",
    "message_data": {
        "id": 42,
        "chat_id": 5,
        "sender_id": 1,
        "content": "Hello! How are you?",
        "timestamp": "2026-01-19 15:45:30",
        "is_read": false,
        "sender_name": "john_doe"
    }
}
```

**Error Response** (400/403)
```json
{
    "error": "Access denied"
}
```

**Side Effects**
- Inserts message into `messages` table with current timestamp
- `is_read` defaults to `false`

---

### 5.4 Fetch Messages
**Endpoint**: `GET /backend/chat/fetch_messages.php?chat_id=5`

**Authentication**: ✅ Required

**Query Parameters**
- `chat_id`: Required, integer - ID of the chat to fetch messages from

**Success Response** (200)
```json
{
    "success": true,
    "messages": [
        {
            "id": 40,
            "chat_id": 5,
            "sender_id": 2,
            "content": "Hi there!",
            "timestamp": "2026-01-19 15:40:00",
            "is_read": true,
            "sender_name": "alice"
        },
        {
            "id": 41,
            "chat_id": 5,
            "sender_id": 1,
            "content": "Hello! How are you?",
            "timestamp": "2026-01-19 15:45:30",
            "is_read": false,
            "sender_name": "john_doe"
        }
    ]
}
```

**Error Response** (400/403)
```json
{
    "error": "Access denied"
}
```

**Logic**
- Verifies user has access to the chat
- Returns messages ordered by timestamp (oldest first)
- Automatically marks all unread messages from other users as read
- Includes sender username for display purposes

---

## 6. Frontend Architecture

### 6.1 Pages

#### **index.php** (Login/Register)
- Redirects authenticated users to `chat.php`
- Contains login and registration forms
- Validates credentials via `/backend/auth/login.php` or `/backend/auth/register.php`
- Stores authenticated user in `localStorage` as JSON
- Uses Bootstrap for styling

#### **chat.php** (Main Chat Interface)
- Redirects unauthenticated users to `index.php` (server-side session check)
- Shows user list in sidebar
- Displays conversation messages in main area
- Features:
  - User selection with active state highlighting
  - Real-time message loading (simulated, not polling yet)
  - Message sending with timestamp
  - Profile button navigation
  - Logout button with PHP session destruction
  - Responsive design (responsive on tablets/mobile)

#### **profile.php** (User Profile)
- Redirects unauthenticated users to `index.php`
- Shows user profile information
- Allows editing username, email, and profile info
- Calls `/backend/auth/updateProfile.php` to persist changes
- Loads user data from `localStorage` for display

### 6.2 JavaScript Objects

#### **chatApp** (chat.php)
Main application object managing chat functionality:

**Properties**
- `currentChatUserId`: Currently selected user's ID

**Methods**
- `init()` - Initializes app, loads user from localStorage, sets up listeners
- `setupEventListeners()` - Attaches click handlers to UI elements
- `selectUser(userId, userElement)` - Switches to conversation with selected user
- `loadConversation(userId, userName)` - Loads messages for selected user (currently mock data)
- `sendMessage()` - Sends message to selected user
- `simulateReply()` - Simulates a reply from the other user
- `scrollToBottom()` - Auto-scrolls message container to latest message
- `showMessageInput()` / `hideMessageInput()` - Toggles message input visibility

### 6.3 Client-Side Data Flow

```
Login/Register (index.php)
        ↓
Auth API Response → localStorage (user object)
        ↓
chat.php loads (reads from localStorage)
        ↓
User Selection → selectUser() → loadConversation() → Display Messages
        ↓
Send Message → sendMessage() → (Currently mock, not calling backend yet)
        ↓
Logout → fetch() to logout.php → localStorage cleared → redirect to index.php
```

---

## 7. Current Issues & Improvements Needed

### Critical Issues
1. **updateProfile.php** - References non-existent `../config/database.php` instead of using the singleton from `db.php`
2. **Chat messages** - Frontend doesn't yet call the message API endpoints (fetch_messages.php, send_message.php)
3. **User list** - Frontend doesn't call fetch_users.php; uses hardcoded sample users
4. **Start chat** - Frontend doesn't call start_chat.php; chat_id is not validated

### Frontend Gaps
- No real API integration for message fetching/sending
- No polling or WebSocket for real-time updates
- No message read status display
- No user online/offline status tracking
- No chat search or filtering

### Security Considerations
- ✅ Passwords are hashed with bcrypt
- ✅ Session-based authentication
- ✅ Access control checks on chat endpoints
- ✅ Input validation on all endpoints
- ⚠️ Missing CSRF tokens on POST requests
- ⚠️ No rate limiting
- ⚠️ No input sanitization for XSS prevention (using innerHTML)

---

## 8. API Request/Response Summary Table

| Method | Endpoint | Auth | Purpose | Status |
|--------|----------|------|---------|--------|
| POST | `/auth/register.php` | ❌ | Create new user account | ✅ Ready |
| POST | `/auth/login.php` | ❌ | Authenticate user | ✅ Ready |
| POST | `/auth/logout.php` | ✅ | Destroy session | ✅ Ready |
| POST | `/auth/updateProfile.php` | ✅ | Update user profile | ⚠️ Bug in config |
| GET | `/chat/fetch_users.php` | ✅ | Get all users (except current) | ✅ Ready |
| POST | `/chat/start_chat.php` | ✅ | Create/get chat with user | ✅ Ready |
| POST | `/chat/send_message.php` | ✅ | Send message in chat | ✅ Ready |
| GET | `/chat/fetch_messages.php` | ✅ | Get messages from chat | ✅ Ready |

---

## 9. Setup & Deployment

### Prerequisites
- PHP 7.0+
- MySQL 5.7+
- Web server (Apache with mod_rewrite or built-in PHP server)

### Database Setup
```sql
mysql -u root -p < db/schema.sql
mysql -u root -p minichatapp < db/sample_data.sql
```

### Running the Application
```bash
# Using PHP built-in server
php -S localhost:8000

# Access via browser
http://localhost:8000/public/index.php
```

### Configuration
Edit `backend/config/db.php` to set database credentials:
```php
private $host = "localhost";
private $db_name = "minichatapp";
private $username = "root";
private $password = "";
```

---

## 10. Example API Usage

### Register Example
```bash
curl -X POST http://localhost:8000/backend/auth/register.php \
  -H "Content-Type: application/json" \
  -d '{
    "username": "testuser",
    "email": "test@example.com",
    "password": "password123",
    "profile_info": "Test user"
  }'
```

### Login Example
```bash
curl -X POST http://localhost:8000/backend/auth/login.php \
  -H "Content-Type: application/json" \
  -c cookies.txt \
  -d '{
    "email": "test@example.com",
    "password": "password123"
  }'
```

### Fetch Users Example
```bash
curl -X GET http://localhost:8000/backend/chat/fetch_users.php \
  -b cookies.txt
```

### Send Message Example
```bash
curl -X POST http://localhost:8000/backend/chat/send_message.php \
  -H "Content-Type: application/json" \
  -b cookies.txt \
  -d '{
    "chat_id": 1,
    "content": "Hello world!"
  }'
```

---

## Summary

MiniChatApp is a foundational chat application with a complete backend API and partially implemented frontend. The backend supports full user authentication, chat management, and message handling. The frontend is UI-complete but requires integration with backend APIs for real-time messaging functionality.

**Next Steps for Completion:**
1. Fix updateProfile.php database configuration bug
2. Integrate frontend with actual message API endpoints
3. Add polling or WebSocket for real-time updates
4. Implement CSRF protection
5. Add input sanitization for XSS prevention
6. Add rate limiting to API endpoints
7. Implement user online/offline status
8. Add message search and filtering
