const express = require('express');
const http = require('http');
const { Server } = require('socket.io');
const jwt = require('jsonwebtoken'); // We'll install this in the next sub-step

const app = express();
const server = http.createServer(app);
const io = new Server(server, {
  cors: {
    origin: "http://localhost", // Change to your PHP app's origin (e.g., http://localhost if WAMP is on port 80)
    methods: ["GET", "POST"],
    credentials: true
  }
});

// Secret key for JWT – in production, use env var or secret manager
// IMPORTANT: Must be 32+ chars for HS256 algorithm
const JWT_SECRET = 'MiniChatApp_SecretKey_2025_v1_xyz';

// Track online users (userId -> connection count)
const onlineUsers = new Map();

// Middleware to authenticate socket connections
io.use((socket, next) => {
  const token = socket.handshake.auth.token;
  if (!token) {
    console.error('❌ [AUTH] No token provided in handshake');
    return next(new Error('Authentication error: No token provided'));
  }
  
  console.log('[AUTH] Token received, length:', token.length);
  
  try {
    const decoded = jwt.verify(token, JWT_SECRET);
    console.log('✅ [AUTH] Token verified successfully! User ID:', decoded.id);
    socket.user = decoded; // Attach user info to socket (e.g., user_id)
    next();
  } catch (err) {
    console.error('❌ [AUTH] Token verification failed:', err.message);
    console.error('    Secret used:', JWT_SECRET);
    console.error('    Token (first 50 chars):', token.substring(0, 50));
    next(new Error('Authentication error: Invalid token'));
  }
});

// Basic route to check server is alive
app.get('/', (req, res) => {
  res.send('WebSocket server is running adem 🟢');
});

// When someone connects
io.on('connection', (socket) => {
  const userId = String(socket.user.id);
  socket.data.userId = userId;
  console.log('New client connected:', userId); // Now we have user.id from token

  const currentCount = onlineUsers.get(userId) || 0;
  onlineUsers.set(userId, currentCount + 1);

  if (currentCount === 0) {
    io.emit('userStatus', { userId, status: 'online' });
  }

  io.emit('onlineUsers', Array.from(onlineUsers.keys()));
  console.log('[presence] onlineUsers broadcast (connect):', Array.from(onlineUsers.keys()));

  // Join user's personal room (for private notifications)
  socket.join(`user_${userId}`);

  // Listen for joining a chat room
  socket.on('join chat', (chatId) => {
    socket.join(`chat_${chatId}`);
    console.log(`${userId} joined chat_${chatId}`);
  });

  // Listen for a message
  socket.on('chat message', (data) => {
    const { chatId, message } = data;
    console.log(`Message in chat ${chatId} from ${userId}: ${message}`);
    
    // Broadcast to the specific chat room (only participants get it)
    io.to(`chat_${chatId}`).emit('chat message', {
      chatId,
      senderId: userId,
      message,
      timestamp: new Date().toISOString()
    });
  });

  socket.on('requestOnlineUsers', () => {
    console.log('[presence] requestOnlineUsers from', userId);
    socket.emit('onlineUsers', Array.from(onlineUsers.keys()));
  });

  // Handle disconnect
  socket.on('disconnect', () => {
    const storedUserId = socket.data.userId;
    console.log('Client disconnected:', storedUserId);

    if (!storedUserId) return;

    const count = (onlineUsers.get(storedUserId) || 1) - 1;
    if (count <= 0) {
      onlineUsers.delete(storedUserId);
      io.emit('userStatus', { userId: storedUserId, status: 'offline' });
    } else {
      onlineUsers.set(storedUserId, count);
    }

    io.emit('onlineUsers', Array.from(onlineUsers.keys()));
    console.log('[presence] onlineUsers broadcast (disconnect):', Array.from(onlineUsers.keys()));
  });
});

// Start the server
const PORT = 3000;
server.listen(PORT, () => {
  console.log(`Socket.io server running on http://localhost:${PORT}`);
});
