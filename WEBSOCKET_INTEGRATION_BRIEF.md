# WebSocket Integration Brief - MiniChatApp

**Purpose**: This document contains all necessary information for an AI assistant to design and provide precise WebSocket integration instructions for our chat application.

---

## 1. Current Backend Setup

### Technology Stack
- **Language**: PHP 7+
- **Database**: MySQL 5.7+
- **Server**: Apache/WAMP64
- **Current API Pattern**: RESTful JSON endpoints

### Database Configuration (backend/config/db.php)
```php
Host: localhost
Database: minichatapp
User: root
Password: (empty)
Charset: utf8mb4
Connection: PDO Singleton Pattern
```

---

## 2. Database Schema

### users Table
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

### chats Table
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

### messages Table
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

---

## 3. Backend Helper Functions (backend/config/db.php)

### Database Singleton
```php
Database::getInstance()
```
Returns PDO connection instance. Singleton pattern ensures single connection.

### Authentication Functions
```php
// Check if user is logged in
isLoggedIn() -> boolean

// Enforce authentication (exits with JSON error if not logged in)
requireLogin() -> void

// Get current user's ID from session
getUserID() -> int|null

// Send JSON response with HTTP status
jsonResponse($data, $status = 200) -> void
```

### Session Management
```php
session_start();
$_SESSION['user_id']    // Current user ID
$_SESSION['username']   // Current user username
$_SESSION['email']      // Current user email
```

---

## 4. Existing API Endpoints

### Authentication Endpoints

#### POST /backend/auth/register.php
- **Auth**: Not required
- **Body**: `{username, email, password, profile_info}`
- **Response**: User object + sets session

#### POST /backend/auth/login.php
- **Auth**: Not required
- **Body**: `{email, password}`
- **Response**: User object + sets session

#### POST /backend/auth/logout.php
- **Auth**: Required
- **Body**: Empty JSON
- **Response**: `{success: true}`
- **Side Effect**: Destroys session

#### POST /backend/auth/updateProfile.php
- **Auth**: Required
- **Body**: `{username, email, profile_info}`
- **Response**: Updated user object

### Chat Endpoints

#### GET /backend/chat/fetch_users.php
- **Auth**: Required
- **Response**: Array of all users except current user

#### POST /backend/chat/start_chat.php
- **Auth**: Required
- **Body**: `{user_id}`
- **Response**: Chat object with IDs and user names

#### POST /backend/chat/send_message.php
- **Auth**: Required
- **Body**: `{chat_id, content}`
- **Response**: Created message object with metadata

#### GET /backend/chat/fetch_messages.php?chat_id=ID
- **Auth**: Required
- **Response**: Array of messages with sender info, marks as read

---

## 5. Current Frontend Implementation

### Login/Register (public/index.php)
- Session check: Redirects authenticated users to chat.php
- Form submission to `/backend/auth/login.php` or `/backend/auth/register.php`
- Stores user in localStorage as JSON
- No WebSocket: REST-based authentication

### Chat Interface (public/chat.php)
- Session check: Redirects unauthenticated users to index.php
- User list: Hardcoded sample data (not calling fetch_users.php yet)
- Message display: Hardcoded sample conversations
- Logout: Calls `/backend/auth/logout.php` → redirects to index.php
- Profile: Redirects to profile.php
- **Current Flow**: Entirely client-side with mock data, no real-time communication

### JavaScript Objects (chat.php)
```javascript
chatApp = {
    currentChatUserId: null,
    init()
    setupEventListeners()
    selectUser(userId, userElement)
    loadConversation(userId, userName)
    sendMessage()
    simulateReply()
    scrollToBottom()
}
```

### Client-Side Storage
```javascript
localStorage.user = JSON.parse({
    id, username, email, profile_info
})
```

---

## 6. Current Message Flow

```
┌─────────────────────────────────────────┐
│ User opens chat.php                      │
├─────────────────────────────────────────┤
│ 1. Session check (server-side PHP)      │
│ 2. Load localStorage user data          │
│ 3. Show hardcoded user list             │
│ 4. Show hardcoded sample messages       │
│ 5. User clicks user → loadConversation()│
│ 6. User sends message → sendMessage()   │
│ 7. simulateReply() creates fake reply   │
│ 8. No backend communication (mock only) │
└─────────────────────────────────────────┘
```

---

## 7. Gaps & Limitations (Current State)

### Frontend Gaps
- ❌ Does NOT call `/backend/chat/fetch_users.php`
- ❌ Does NOT call `/backend/chat/start_chat.php`
- ❌ Does NOT call `/backend/chat/send_message.php`
- ❌ Does NOT call `/backend/chat/fetch_messages.php`
- ❌ No real-time updates (no polling, no WebSocket)
- ❌ No user online/offline status
- ❌ No message read receipts
- ❌ Simulated replies only (not from real users)

### Backend Capabilities (Fully Ready)
- ✅ Complete REST API implemented
- ✅ Database fully functional
- ✅ User authentication & session management
- ✅ Chat creation & message storage
- ✅ Access control on sensitive endpoints

---

## 8. WebSocket Integration Requirements

### What We Need to Implement

1. **WebSocket Server Component**
   - Language: PHP or Node.js (separate from REST API)
   - Port: (to be determined, e.g., 8080)
   - Purpose: Handle persistent connections, broadcast messages in real-time

2. **Client-Side WebSocket Connection**
   - Connect on chat.php load
   - Authenticate using existing session/token
   - Send/receive messages in real-time
   - Handle connection state (online/offline indicators)

3. **Hybrid Architecture**
   - **REST API**: Handle authentication, profile updates, user listing
   - **WebSocket**: Handle real-time message exchange, presence status

4. **Message Broadcasting**
   - When user sends message → store in DB via REST → broadcast via WebSocket to recipient
   - Real-time delivery confirmation
   - Typing indicators (optional)

5. **User Presence**
   - Track which users are online/offline
   - Update UI with status indicators
   - Persist status in database or memory store (Redis optional)

---

## 9. Questions for Director AI

We need guidance on:

1. **WebSocket Library/Framework**: 
   - Use Ratchet (PHP WebSocket)?
   - Use Node.js with Socket.io?
   - Use separate service?

2. **Authentication Strategy**:
   - How to authenticate WebSocket connections using existing sessions?
   - Token-based or session cookie forwarding?

3. **Message Flow**:
   - Should messages be sent directly via WebSocket or REST + broadcast?
   - How to ensure message persistence in database?

4. **Scalability**:
   - What if we scale to multiple server instances?
   - Redis for session/message broadcasting?

5. **Architecture Decision**:
   ```
   Option A: Separate WebSocket Server
   Frontend → WebSocket Server (real-time messages)
   Frontend → REST API (auth, profile, etc.)
   
   Option B: Integrated WebSocket in PHP
   Frontend → Single PHP Server (REST + WebSocket)
   Downside: Blocking I/O challenges
   
   Option C: Node.js Wrapper
   Frontend → Node.js Server (WebSocket)
   Node.js ↔ PHP Backend (REST communication)
   ```

6. **Implementation Scope**:
   - Minimal MVP: Just real-time messaging?
   - Include: Typing indicators, read receipts, presence status?
   - Fallback: What if WebSocket fails (auto-switch to polling)?

7. **Frontend Integration**:
   - Keep existing chatApp object or refactor?
   - Smooth transition from mock data to real data?
   - Error handling for connection loss?

---

## 10. Environment Information

### Server Setup
- **OS**: Windows (WAMP64)
- **PHP Version**: 7+ (need exact version verification)
- **MySQL**: 5.7+ 
- **Apache**: WAMP64 built-in
- **Node.js Available**: Unknown (need verification)
- **Port 8000+**: Likely available for WebSocket server

### Current Project Path
```
c:\wamp64\www\mini_chat_app-main\
├── backend/
├── public/
├── db/
└── API_DOCUMENTATION.md
```

### Deployment Target
- Local development initially
- Production deployment: (TBD)

---

## 11. Summary for Director

**Existing State**:
- ✅ Complete REST API backend (8 endpoints)
- ✅ MySQL database with proper schema
- ✅ PHP session authentication
- ✅ Client-side UI (Bootstrap) fully designed
- ❌ Frontend NOT integrated with backend yet
- ❌ No real-time messaging (all mock data)

**Goal**: Add WebSocket layer for real-time communication while keeping REST API for non-real-time operations.

**Constraints**:
- Windows/WAMP64 environment
- Minimize token usage (requesting clean, structured decisions)
- Existing codebase structure should be preserved
- Smooth migration from mock data to real data

**Priority**:
1. Choose WebSocket architecture (PHP Ratchet vs Node.js vs other)
2. Design authentication flow for WebSocket
3. Provide step-by-step integration instructions
4. Code examples for implementation
5. Deployment instructions

---

## 12. Code References for Director

All backend code is located in:
- `backend/config/db.php` - Database singleton and helpers
- `backend/auth/` - Authentication endpoints
- `backend/chat/` - Chat API endpoints

All frontend code is in:
- `public/index.php` - Login/Register
- `public/chat.php` - Main chat interface (contains inline JavaScript)
- `public/profile.php` - User profile

---

**Last Updated**: January 19, 2026
**Status**: Ready for WebSocket architecture design
