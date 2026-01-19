# WebSocket Integration Documentation - MiniChatApp

## Overview
This document explains the real-time WebSocket implementation in the MiniChatApp project, the errors encountered during debugging, and the solutions applied.

---

## Architecture

### Tech Stack
- **Backend**: PHP 8+ (WAMP) with MySQL database
- **Frontend**: Vanilla JavaScript with Bootstrap 5
- **Real-time**: Node.js + Socket.IO server (separate process)
- **Authentication**: PHP sessions + JWT (JSON Web Tokens)
- **JWT Library**: 
  - PHP: `firebase/php-jwt` (installed via Composer)
  - Node.js: `jsonwebtoken` (installed via npm)

### Components

```
┌─────────────────────────────────────────────────────────────────┐
│                         User Browser                             │
│  ┌────────────────────────────────────────────────────────┐    │
│  │  public/chat.php (Frontend)                            │    │
│  │  - Fetch users via REST API                            │    │
│  │  - Get JWT token from PHP endpoint                     │    │
│  │  - Connect to Socket.IO with token                     │    │
│  │  - Send/receive messages                               │    │
│  └────────────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────────┘
           │                            │
           │ HTTP (REST)                │ WebSocket (Socket.IO)
           │                            │
           ▼                            ▼
┌──────────────────────┐    ┌──────────────────────────┐
│   PHP Backend        │    │   Node.js WebSocket      │
│   (WAMP Server)      │    │   Server                 │
│                      │    │                          │
│  backend/auth/       │    │  websocket-server/       │
│  - get_ws_token.php  │    │  - server.js             │
│    (generates JWT)   │    │    (verifies JWT)        │
│                      │    │    (handles real-time)   │
│  backend/chat/       │    │                          │
│  - fetch_users.php   │    │  Port: 3000              │
│  - start_chat.php    │    │  CORS: localhost         │
│  - send_message.php  │    │                          │
│  - fetch_messages.php│    │                          │
└──────────────────────┘    └──────────────────────────┘
           │                            │
           └────────────┬───────────────┘
                        │
                        ▼
                ┌──────────────┐
                │    MySQL     │
                │  Database    │
                │              │
                │  - users     │
                │  - chats     │
                │  - messages  │
                └──────────────┘
```

---

## How WebSocket Works in This Project

### 1. User Authentication Flow

```javascript
// Step 1: User logs in via PHP (session created)
POST /backend/auth/login.php
→ Creates $_SESSION['user_id']
→ Returns user data to frontend

// Step 2: Frontend stores user in localStorage
localStorage.setItem('user', JSON.stringify({
    id: 3,
    username: 'john',
    email: 'john@example.com'
}));
```

### 2. WebSocket Connection Flow

```javascript
// Step 3: Get JWT token for WebSocket authentication
GET /backend/auth/get_ws_token.php (with session cookie)
→ PHP verifies session
→ Generates JWT: { id: userId, exp: timestamp + 3600 }
→ Returns: { "token": "eyJhbGci..." }

// Step 4: Connect to Socket.IO with JWT
const socket = io('http://localhost:3000', {
    auth: { token: jwt_token },
    reconnection: true
});

// Step 5: Node.js verifies JWT before accepting connection
io.use((socket, next) => {
    const token = socket.handshake.auth.token;
    const decoded = jwt.verify(token, JWT_SECRET);
    socket.user = decoded; // { id: 3, exp: ... }
    next(); // Allow connection
});
```

### 3. Chat Room Flow

```javascript
// Step 6: User clicks on another user in sidebar
→ Frontend calls: POST /backend/chat/start_chat.php
→ Returns: { chat_id: 7, ... }
→ Socket emits: socket.emit('join chat', 7)
→ Node.js: socket.join('chat_7')

// Step 7: User sends a message
→ Frontend: POST /backend/chat/send_message.php (saves to DB)
→ Socket emits: socket.emit('chat message', { chatId: 7, message: 'Hello' })
→ Node.js broadcasts: io.to('chat_7').emit('chat message', data)
→ Other user receives message in real-time
```

---

## Errors Encountered & Solutions

### ❌ Error #1: PHP Parse Error - `unexpected token "use"`

**Error Message:**
```
Parse error: syntax error, unexpected token "use" in 
C:\wamp64\www\MiniChatApp\backend\auth\get_ws_token.php on line 44
```

**Cause:**
- The `use Firebase\JWT\JWT;` statement was placed **inside** the `try-catch` block
- In PHP, `use` statements for namespace imports **must** appear at the top of the file, immediately after `<?php`

**Solution:**
```php
<?php
use Firebase\JWT\JWT;  // ✓ Correct: At the top

// Enable debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../../vendor/autoload.php';
    // ... rest of code
```

**File Modified:** `backend/auth/get_ws_token.php`

---

### ❌ Error #2: JWT "Provided key is too short"

**Error Message:**
```json
{
  "error": "Token generation failed",
  "detail": "Provided key is too short",
  "file": "C:\\wamp64\\www\\MiniChatApp\\vendor\\firebase\\php-jwt\\src\\JWT.php",
  "line": 709
}
```

**Cause:**
- Firebase JWT's HS256 algorithm requires a minimum of **32 bytes (256 bits)** for the secret key
- The placeholder secret `'your_secret_key_here'` was only 20 characters

**Solution:**
Updated both files with a **33-character secret**:

```php
// backend/auth/get_ws_token.php
$JWT_SECRET = 'MiniChatApp_SecretKey_2025_v1_xyz'; // 33 chars ✓
```

```javascript
// websocket-server/server.js
const JWT_SECRET = 'MiniChatApp_SecretKey_2025_v1_xyz'; // Same secret ✓
```

**Critical:** Both PHP and Node.js **must use the exact same secret** for JWT to work.

**Files Modified:**
- `backend/auth/get_ws_token.php`
- `websocket-server/server.js`

---

### ❌ Error #3: "Authentication error: Invalid token"

**Error Message:**
```
chat.php:580 ✗ WebSocket connection error: Authentication error: Invalid token
```

**Cause:**
- The Node.js server wasn't restarted after updating the JWT secret
- Old process was still using the old secret `'your_secret_key_here'`
- PHP was generating tokens with the new secret, but Node.js was rejecting them

**Solution:**
1. **Restart the Node.js server** after any secret changes:
```bash
# Press Ctrl+C in the terminal running Node.js
cd C:\wamp64\www\MiniChatApp\websocket-server
node server.js
```

2. Added debug logging to identify the issue:
```javascript
io.use((socket, next) => {
  console.log('[AUTH] Token received, length:', token.length);
  try {
    const decoded = jwt.verify(token, JWT_SECRET);
    console.log('✅ [AUTH] Token verified successfully! User ID:', decoded.id);
    next();
  } catch (err) {
    console.error('❌ [AUTH] Token verification failed:', err.message);
    next(new Error('Authentication error: Invalid token'));
  }
});
```

**Files Modified:**
- `websocket-server/server.js`

---

### ❌ Error #4: Message Form Not Appearing on User Click

**Error Message:**
```
[selectUser] No chatId returned, aborting
```

**Cause:**
- The API endpoint `backend/chat/start_chat.php` returned:
  ```json
  { "success": true, "chat": { "id": 7, ... } }
  ```
- But the frontend expected:
  ```json
  { "chat_id": 7 }
  ```

**Solution:**
Modified the API to include both formats for compatibility:

```php
// backend/chat/start_chat.php
jsonResponse([
    'success' => true, 
    'chat_id' => $chat_id,  // ✓ Added this
    'chat' => $chat
]);
```

**File Modified:** `backend/chat/start_chat.php`

---

### ❌ Error #5: "messages.forEach is not a function"

**Error Message:**
```
TypeError: messages.forEach is not a function
    at Object.loadConversation (chat.php:760:30)
```

**Cause:**
- The API endpoint `backend/chat/fetch_messages.php` returned:
  ```json
  { "success": true, "messages": [...] }
  ```
- But the frontend treated the entire response as an array:
  ```javascript
  const messages = await res.json(); // ← This is the object, not the array!
  messages.forEach(...); // ✗ Error!
  ```

**Solution:**
Extract the `messages` array from the response object:

```javascript
// public/chat.php - loadConversation()
const data = await res.json();
const messages = data.messages || data || []; // ✓ Extract array

if (!Array.isArray(messages)) {
    throw new Error('Invalid messages format');
}

messages.forEach((msg) => {
    // Now works! ✓
});
```

**File Modified:** `public/chat.php`

---

## Final Working Flow

### Complete Message Send/Receive Cycle

```
User A (Browser)                  PHP Backend                Node.js Server         User B (Browser)
      │                                 │                           │                       │
      │ 1. Click user                   │                           │                       │
      ├──POST start_chat.php────────────►                           │                       │
      │◄──{chat_id: 7}───────────────────┤                           │                       │
      │                                 │                           │                       │
      │ 2. Join chat room               │                           │                       │
      ├──socket.emit('join chat', 7)────┼──────────────────────────►│                       │
      │                                 │       socket.join('chat_7')                       │
      │                                 │                           │                       │
      │ 3. Load messages                │                           │                       │
      ├──GET fetch_messages.php?chat_id=7►                           │                       │
      │◄──{messages: [...]}─────────────┤                           │                       │
      │                                 │                           │                       │
      │ 4. Type & send "Hello"          │                           │                       │
      ├──POST send_message.php──────────►                           │                       │
      │   {chat_id: 7, content: "Hello"}   [Saves to database]      │                       │
      │◄──{success: true}───────────────┤                           │                       │
      │                                 │                           │                       │
      │ 5. Emit real-time event         │                           │                       │
      ├──socket.emit('chat message',────┼──────────────────────────►│                       │
      │   {chatId: 7, message: "Hello"})│              io.to('chat_7').emit()               │
      │                                 │                           ├──────────────────────►│
      │                                 │                           │   socket.on('chat message')
      │                                 │                           │   ◄ User B sees "Hello"
      │                                 │                           │      in real-time! ✓  │
```

---

## Key Files & Their Roles

### Backend PHP Files

| File | Purpose | Returns |
|------|---------|---------|
| `backend/config/db.php` | Database singleton, helper functions (`requireLogin`, `jsonResponse`) | N/A |
| `backend/auth/get_ws_token.php` | Generates JWT token for WebSocket auth | `{"token": "eyJ..."}` |
| `backend/auth/login.php` | User login, creates session | `{"id": 3, "username": "..."}` |
| `backend/chat/fetch_users.php` | Get list of all users | `{"messages": [...]}` |
| `backend/chat/start_chat.php` | Create/get chat between two users | `{"chat_id": 7, "chat": {...}}` |
| `backend/chat/send_message.php` | Save message to database | `{"success": true, "message_id": 123}` |
| `backend/chat/fetch_messages.php` | Get all messages in a chat | `{"messages": [...]}` |

### Frontend JavaScript (public/chat.php)

| Function | Purpose |
|----------|---------|
| `chatApp.init()` | Initialize app on page load |
| `fetchUsers()` | Load user list via AJAX |
| `connectWebSocket()` | Get JWT & connect to Socket.IO |
| `selectUser(userId)` | Handle user click, load chat |
| `getOrCreateChat(userId)` | Get/create chat ID |
| `loadConversation(chatId)` | Load message history |
| `sendMessage()` | Send message (DB + Socket) |
| `displayMessage()` | Render message in UI |

### WebSocket Server (websocket-server/server.js)

| Section | Purpose |
|---------|---------|
| JWT Authentication Middleware | Verifies token before allowing connection |
| `io.on('connection')` | Handles new client connections |
| `socket.on('join chat')` | User joins a specific chat room |
| `socket.on('chat message')` | Broadcasts message to chat room |
| `socket.on('disconnect')` | Cleanup when user disconnects |

---

## Security Considerations

### ✅ Implemented Security Measures

1. **Session-based authentication** for PHP endpoints
   - `requireLogin()` checks `$_SESSION['user_id']`
   
2. **JWT tokens for WebSocket** with 1-hour expiration
   ```php
   'exp' => time() + 3600 // Token expires in 1 hour
   ```

3. **CORS restrictions** on Socket.IO server
   ```javascript
   cors: {
       origin: "http://localhost",
       credentials: true
   }
   ```

4. **Database access validation**
   - Users can only access their own chats
   - SQL uses prepared statements (prevents injection)

### ⚠️ Production Recommendations

1. **Move JWT secret to environment variables**
   ```bash
   # .env file
   JWT_SECRET=your_very_long_random_secret_key_here_64_chars_min
   ```

2. **Use HTTPS/WSS in production**
   ```javascript
   const socket = io('https://yourdomain.com', { ... });
   ```

3. **Add rate limiting** to prevent spam

4. **Implement refresh tokens** for long sessions

5. **Add input sanitization** to prevent XSS attacks

---

## Debugging Tips

### Check if WebSocket Server is Running
```bash
# In browser console:
curl http://localhost:3000
# Should see: "WebSocket server is running 🟢"
```

### View Server Logs
```bash
# Node.js terminal shows:
[AUTH] Token received, length: 115
✅ [AUTH] Token verified successfully! User ID: 3
New client connected: 3
3 joined chat_7
```

### Check Browser Console
```javascript
// Expected successful flow:
Attempting to fetch WebSocket token...
Token response received: {"token":"eyJ...
Token obtained successfully, connecting to socket.io...
✓ WebSocket connected! Socket ID: A4bC1d2E3fG...
[selectUser] START - userId: 5
[getOrCreateChat] Returning chat_id: 7
[loadConversation] Messages array: [...] length: 3
```

### Common Issues

| Symptom | Cause | Fix |
|---------|-------|-----|
| 500 error on token endpoint | Missing vendor/autoload.php | Run `composer install` |
| "Invalid token" error | Server not restarted | Restart Node.js: `node server.js` |
| "Port already in use" | Server already running | Find process: `netstat -ano | findstr :3000` |
| Messages not appearing | API returns object not array | Check `data.messages` extraction |
| Click not working | Event listener not attached | Check `console.log` for errors |

---

## Testing Checklist

- [x] User can login via PHP
- [x] JWT token is generated successfully
- [x] WebSocket connects with valid token
- [x] User list loads from database
- [x] Clicking user shows message form
- [x] Previous messages load correctly
- [x] Can send new messages
- [x] Messages save to database
- [x] Real-time messages broadcast via Socket.IO
- [x] Other users receive messages instantly
- [x] Console shows detailed debug logs

---

## Performance Notes

- **Connection pooling**: PHP uses PDO singleton pattern
- **Message persistence**: All messages saved to MySQL before broadcasting
- **Room-based broadcasting**: Only users in specific chat rooms receive messages
- **Automatic reconnection**: Socket.IO retries up to 5 times if disconnected

---

## Environment Setup

### PHP Dependencies (Composer)
```json
{
    "require": {
        "firebase/php-jwt": "^7.0"
    }
}
```

### Node.js Dependencies (npm)
```json
{
    "dependencies": {
        "express": "^4.18.0",
        "socket.io": "^4.5.0",
        "jsonwebtoken": "^9.0.0"
    }
}
```

### Installation Commands
```bash
# PHP
cd C:\wamp64\www\MiniChatApp
composer install

# Node.js
cd websocket-server
npm install
```

### Start Services
```bash
# 1. Start WAMP (Apache + MySQL)
# 2. Start Node.js WebSocket server
cd C:\wamp64\www\MiniChatApp\websocket-server
node server.js

# 3. Open browser
http://localhost/minichatapp/public/chat.php
```

---

## Conclusion

The WebSocket integration is now fully functional with:
- ✅ Secure JWT authentication
- ✅ Real-time bidirectional communication
- ✅ Persistent message storage
- ✅ Room-based message broadcasting
- ✅ Comprehensive error handling
- ✅ Detailed debug logging

All major errors have been resolved, and the chat application supports real-time messaging between multiple users simultaneously.

---

**Last Updated:** January 19, 2026  
**Author:** GitHub Copilot  
**Project:** MiniChatApp - Real-time Chat Application
