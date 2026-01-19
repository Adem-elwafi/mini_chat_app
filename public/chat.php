<?php
// protéger la page chat pour que seuls les utilisateurs connectés puissent y accéder.
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Socket.IO client -->
    <script src="http://localhost:3000/socket.io/socket.io.js"></script>
    <title>MiniChatApp - Chat</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3a0ca3;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --success-color: #4cc9f0;
            --warning-color: #f72585;
            --gray-color: #adb5bd;
            --sidebar-width: 320px;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fb;
            height: 100vh;
            overflow: hidden;
        }

        .chat-container {
            height: 100vh;
            max-height: 100vh;
            overflow: hidden;
            border-radius: 0;
        }

        .users-sidebar {
            background-color: white;
            border-right: 1px solid #e9ecef;
            height: 100vh;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }

        .user-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .current-user {
            display: flex;
            align-items: center;
        }

        .avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: var(--success-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.3rem;
            margin-right: 15px;
            border: 3px solid rgba(255,255,255,0.3);
        }

        .user-info h3 {
            margin: 0;
            font-size: 1.2rem;
        }

        .user-info .email {
            opacity: 0.8;
            font-size: 0.9rem;
        }

        .users-list {
            flex: 1;
            overflow-y: auto;
            padding: 1rem 0;
        }

        .user-item {
            display: flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            cursor: pointer;
            transition: all 0.2s;
            border-bottom: 1px solid #f1f3f9;
        }

        .user-item:hover {
            background-color: #f8f9fa;
        }

        .user-item.active {
            background-color: #eef2ff;
            border-left: 4px solid var(--primary-color);
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #e9ecef;
            color: var(--dark-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 15px;
        }

        .user-status {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-left: auto;
        }

        .user-status.online {
            background-color: #28a745;
        }

        .user-status.offline {
            background-color: #6c757d;
        }

        .user-status.away {
            background-color: #ffc107;
        }

        .chat-area {
            display: flex;
            flex-direction: column;
            height: 100vh;
            background-color: #f8fafc;
        }

        .chat-header {
            background-color: white;
            padding: 1.2rem 1.5rem;
            border-bottom: 1px solid #e9ecef;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            z-index: 10;
        }

        .chat-header h3 {
            margin: 0;
            font-size: 1.3rem;
            color: var(--dark-color);
        }

        .chat-header .selected-user-info {
            display: flex;
            align-items: center;
        }

        .messages-container {
            flex: 1;
            overflow-y: auto;
            padding: 1.5rem;
            background-color: #f8fafc;
        }

        .message {
            margin-bottom: 1.2rem;
            max-width: 70%;
        }

        .message.sent {
            margin-left: auto;
        }

        .message-content {
            padding: 0.8rem 1.2rem;
            border-radius: 18px;
            position: relative;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .message.received .message-content {
            background-color: white;
            border-top-left-radius: 5px;
        }

        .message.sent .message-content {
            background-color: var(--primary-color);
            color: white;
            border-top-right-radius: 5px;
        }

        .message-time {
            font-size: 0.75rem;
            opacity: 0.7;
            margin-top: 0.3rem;
            text-align: right;
        }

        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #6c757d;
            text-align: center;
            padding: 2rem;
        }

        .empty-state .icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .empty-state p {
            font-size: 1.2rem;
            max-width: 400px;
        }

        .message-input {
            background-color: white;
            padding: 1.2rem 1.5rem;
            border-top: 1px solid #e9ecef;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.05);
        }

        .message-input form {
            display: flex;
            gap: 10px;
        }

        .message-input input {
            flex: 1;
            border-radius: 25px;
            padding: 0.75rem 1.5rem;
            border: 1px solid #dee2e6;
            outline: none;
            transition: all 0.3s;
        }

        .message-input input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25);
        }

        .btn-send {
            background-color: var(--primary-color);
            color: white;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            transition: all 0.3s;
        }

        .btn-send:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
        }

        .sidebar-footer {
            padding: 1.5rem;
            background-color: #f8f9fa;
            border-top: 1px solid #e9ecef;
        }

        .sidebar-footer .btn {
            width: 100%;
            margin-bottom: 0.75rem;
            border-radius: 8px;
            padding: 0.75rem;
            font-weight: 500;
        }

        .btn-profile {
            background-color: white;
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
        }

        .btn-logout {
            background-color: var(--warning-color);
            color: white;
            border: none;
        }

        /* Responsive adjustments */
        @media (max-width: 992px) {
            .chat-container {
                overflow: auto;
            }
            
            .users-sidebar {
                height: auto;
                max-height: 50vh;
                border-right: none;
                border-bottom: 1px solid #e9ecef;
            }
            
            .chat-area {
                height: auto;
                min-height: 50vh;
            }
        }

        @media (max-width: 576px) {
            .message {
                max-width: 85%;
            }
            
            .message-input form {
                flex-direction: column;
            }
            
            .btn-send {
                align-self: flex-end;
                width: 100%;
                border-radius: 25px;
            }
        }

        /* Scrollbar styling */
        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
    </style>
</head>
<body>
    <div class="container-fluid p-0 chat-container">
        <div class="row g-0 h-100">
            <!-- Liste des utilisateurs --> 
            <div class="col-lg-4 col-xl-3 users-sidebar">
                <div class="user-header">
                    <div class="current-user">
                        <div class="avatar" id="userAvatar"></div>
                        <div class="user-info">
                            <h3 id="userName">John Doe</h3>
                            <div class="email" id="userEmail">john@example.com</div>
                        </div>
                    </div>
                </div>
                
                <div class="users-list" id="usersList">
                    
                    <!-- Example users (will be populated by JavaScript) -->
                    <div class="user-item" data-user-id="1">
                        <div class="user-avatar">JD</div>
                        <div>
                            <div class="fw-bold">John Doe</div>
                            <small class="text-muted">Online</small>
                        </div>
                        <div class="user-status online"></div>
                    </div>
                    
                    <div class="user-item" data-user-id="2">
                        <div class="user-avatar">AJ</div>
                        <div>
                            <div class="fw-bold">Alice Johnson</div>
                            <small class="text-muted">Last seen 2 hours ago</small>
                        </div>
                        <div class="user-status offline"></div>
                    </div>
                    
                    <div class="user-item" data-user-id="3">
                        <div class="user-avatar">BS</div>
                        <div>
                            <div class="fw-bold">Bob Smith</div>
                            <small class="text-muted">Online</small>
                        </div>
                        <div class="user-status online"></div>
                    </div>
                    
                    <div class="user-item" data-user-id="4">
                        <div class="user-avatar">CR</div>
                        <div>
                            <div class="fw-bold">Carol Roberts</div>
                            <small class="text-muted">Away</small>
                        </div>
                        <div class="user-status away"></div>
                    </div>
                    
                    <div class="user-item" data-user-id="5">
                        <div class="user-avatar">DM</div>
                        <div>
                            <div class="fw-bold">David Miller</div>
                            <small class="text-muted">Last seen yesterday</small>
                        </div>
                        <div class="user-status offline"></div>
                    </div>
                </div>
                
                <div class="sidebar-footer">
                    <button class="btn btn-profile" id="profileBtn">
                        <i class="bi bi-person-circle me-2"></i>My Profile
                    </button>
                    <button class="btn btn-logout" id="logoutBtn">
                        <i class="bi bi-box-arrow-right me-2"></i>Logout
                    </button>
                </div>
            </div>
            
            <!-- Zone de chat -->
            <div class="col-lg-8 col-xl-9 chat-area">
                <div class="chat-header">
                    <div class="selected-user-info" id="selectedUserInfo" style="display: none;">
                        <div class="user-avatar me-3" id="selectedUserAvatar"></div>
                        <div>
                            <h3 id="selectedUserName"></h3>
                            <small class="text-muted" id="selectedUserStatus"></small>
                        </div>
                    </div>
                    <h3 id="defaultHeader">Select a user to start chatting</h3>
                </div>
                
                <div class="messages-container" id="messagesContainer">
                    <!-- Empty state shown when no conversation is selected -->
                    <div class="empty-state" id="emptyState">
                        <div class="icon">💬</div>
                        <h4 class="mb-3">Welcome to MiniChatApp</h4>
                        <p>Select a user from the sidebar to start a conversation</p>
                    </div>
                    
                    <!-- Conversation messages will be loaded here -->
                    <div id="conversationMessages"></div>
                </div>
                
                <div class="message-input" style="display: none;" id="messageInput">
                    <form id="messageForm">
                        <div class="d-flex">
                            <input type="text" placeholder="Type your message..." id="messageText" class="form-control">
                            <button type="submit" class="btn btn-send ms-2">
                                <i class="bi bi-send-fill"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        const chatApp = {
            currentChatUserId: null,
            currentChatId: null,
            socket: null,

            init: function() {
                console.log('Chat app initializing...');

                try {
                    const user = JSON.parse(localStorage.getItem('user'));
                    if (user) {
                        document.getElementById('userName').textContent = user.username || 'User';
                        document.getElementById('userEmail').textContent = user.email || 'user@example.com';
                        document.getElementById('userAvatar').textContent = (user.username || 'U').charAt(0).toUpperCase();
                    }
                } catch (error) {
                    console.error('Error loading user from localStorage:', error);
                }

                this.fetchUsers();
                this.connectWebSocket();
                this.setupEventListeners();

                window.chatApp = this;
                console.log('Chat app initialized successfully');
            },

            fetchUsers: async function() {
                try {
                    const response = await fetch('../backend/chat/fetch_users.php', {
                        credentials: 'same-origin'
                    });

                    if (!response.ok) throw new Error(`HTTP ${response.status}`);

                    const payload = await response.json();
                    const users = Array.isArray(payload) ? payload : payload.users;
                    if (!Array.isArray(users)) throw new Error('Unexpected users payload');

                    const usersList = document.getElementById('usersList');
                    usersList.innerHTML = '';

                    users.forEach((user) => {
                        const item = document.createElement('div');
                        item.className = 'user-item';
                        item.dataset.userId = user.id;
                        item.innerHTML = `
                            <div class="user-avatar">${user.username.charAt(0).toUpperCase()}</div>
                            <div>
                                <div class="fw-bold">${user.username}</div>
                                <small class="text-muted">Offline</small>
                            </div>
                            <div class="user-status offline"></div>
                        `;
                        usersList.appendChild(item);
                    });
                } catch (err) {
                    console.error('Failed to load users:', err);
                }
            },

            connectWebSocket: async function() {
                try {
                    console.log('Attempting to fetch WebSocket token...');
                    
                    const res = await fetch('../backend/auth/get_ws_token.php', {
                        credentials: 'same-origin'
                    });

                    if (!res.ok) {
                        const errorText = await res.text();
                        console.error(`Token fetch failed with status ${res.status}:`, errorText);
                        throw new Error(`HTTP ${res.status}: Cannot get websocket token`);
                    }

                    const raw = await res.text();
                    console.log('Token response received:', raw.substring(0, 50) + '...');
                    
                    let token;
                    try {
                        const data = JSON.parse(raw);
                        token = data.token;
                        if (!token) {
                            throw new Error('Token field is missing in response');
                        }
                    } catch (parseErr) {
                        console.error('Failed to parse token response as JSON:', raw);
                        throw parseErr;
                    }

                    console.log('Token obtained successfully, connecting to socket.io...');

                    this.socket = io('http://localhost:3000', {
                        auth: { token },
                        reconnection: true,
                        reconnectionAttempts: 5
                    });

                    this.socket.on('connect', () => {
                        console.log('✓ WebSocket connected! Socket ID:', this.socket.id);
                    });

                    this.socket.on('disconnect', () => {
                        console.warn('⚠ WebSocket disconnected');
                    });

                    this.socket.on('connect_error', (err) => {
                        console.error('✗ WebSocket connection error:', err.message);
                    });

                    this.socket.on('chat message', (data) => {
                        if (this.currentChatId === data.chatId) {
                            this.displayMessage(data.senderId, data.message, data.timestamp, 'received');
                        }
                    });
                } catch (err) {
                    console.error('✗ WebSocket initialization failed:', err.message);
                    console.error('Full error:', err);
                    // Don't crash the entire app if WebSocket fails
                    this.socket = null;
                }
            },

            setupEventListeners: function() {
                console.log('Setting up event listeners...');

                const logoutBtn = document.getElementById('logoutBtn');
                if (logoutBtn) {
                    logoutBtn.addEventListener('click', () => {
                        window.location.href = '../backend/auth/logout.php';
                    });
                }

                const profileBtn = document.getElementById('profileBtn');
                if (profileBtn) {
                    profileBtn.addEventListener('click', () => {
                        window.location.href = 'profile.php';
                    });
                }

                const messageForm = document.getElementById('messageForm');
                if (messageForm) {
                    messageForm.addEventListener('submit', (e) => {
                        e.preventDefault();
                        this.sendMessage();
                    });
                }

                const usersList = document.getElementById('usersList');
                if (usersList) {
                    console.log('✓ usersList element found, adding click listener');
                    usersList.addEventListener('click', (event) => {
                        console.log('Click detected on usersList, target:', event.target);
                        const userItem = event.target.closest('.user-item');
                        console.log('Closest user-item:', userItem);
                        if (!userItem) {
                            console.log('No user-item found, ignoring click');
                            return;
                        }

                        const userId = userItem.dataset.userId;
                        console.log('✓ User clicked via delegation, userId:', userId, 'type:', typeof userId);
                        this.selectUser(userId, userItem);
                    });
                } else {
                    console.error('✗ usersList element NOT found!');
                }

                console.log('Event listeners setup complete');
            },

            selectUser: async function(userId, userElement) {
                console.log('[selectUser] START - userId:', userId, 'userElement:', userElement);
                
                if (!userElement) {
                    console.log('[selectUser] userElement not provided, searching for it...');
                    userElement = document.querySelector(`.user-item[data-user-id="${userId}"]`);
                    console.log('[selectUser] Found userElement:', userElement);
                }

                document.querySelectorAll('.user-item').forEach((item) => item.classList.remove('active'));
                if (userElement) userElement.classList.add('active');

                const userName = userElement?.querySelector('.fw-bold')?.textContent || 'Unknown User';
                const userStatus = userElement?.querySelector('.text-muted')?.textContent || '';
                const userAvatar = userElement?.querySelector('.user-avatar')?.textContent || '?';

                console.log('[selectUser] User info extracted:', { userName, userStatus, userAvatar });

                const defaultHeader = document.getElementById('defaultHeader');
                const selectedUserInfo = document.getElementById('selectedUserInfo');
                console.log('[selectUser] Headers found:', { defaultHeader, selectedUserInfo });
                
                if (defaultHeader) defaultHeader.style.display = 'none';
                if (selectedUserInfo) selectedUserInfo.style.display = 'flex';
                
                document.getElementById('selectedUserName').textContent = userName;
                document.getElementById('selectedUserStatus').textContent = userStatus;
                document.getElementById('selectedUserAvatar').textContent = userAvatar;

                const emptyState = document.getElementById('emptyState');
                const conversationMessages = document.getElementById('conversationMessages');
                console.log('[selectUser] Message containers found:', { emptyState, conversationMessages });
                
                if (emptyState) emptyState.style.display = 'none';
                if (conversationMessages) {
                    conversationMessages.innerHTML = '';
                    conversationMessages.style.display = 'block';
                }

                console.log('[selectUser] Getting or creating chat for user', userId);

                const chatId = await this.getOrCreateChat(userId);
                console.log('[selectUser] Chat ID received:', chatId);
                
                if (!chatId) {
                    console.error('[selectUser] No chatId returned, aborting');
                    return;
                }

                this.currentChatId = chatId;
                this.currentChatUserId = userId;
                console.log('[selectUser] Current chat set:', { chatId, userId });

                if (this.socket?.connected) {
                    console.log('[selectUser] Emitting join chat event for chatId:', chatId);
                    this.socket.emit('join chat', chatId);
                } else {
                    console.warn('[selectUser] Socket not connected, cannot join chat room');
                }

                await this.loadConversation(chatId);
                console.log('[selectUser] Conversation loaded, showing message input...');
                this.showMessageInput();
                console.log('[selectUser] COMPLETE');
            },

            getOrCreateChat: async function(otherUserId) {
                console.log('[getOrCreateChat] START - otherUserId:', otherUserId);
                try {
                    const requestBody = { user_id: otherUserId };
                    console.log('[getOrCreateChat] Sending request:', requestBody);
                    
                    const res = await fetch('../backend/chat/start_chat.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        credentials: 'same-origin',
                        body: JSON.stringify(requestBody)
                    });

                    console.log('[getOrCreateChat] Response status:', res.status, res.statusText);

                    if (!res.ok) {
                        const errorText = await res.text();
                        console.error('[getOrCreateChat] Response not OK:', errorText);
                        throw new Error('Failed to start/get chat');
                    }

                    const responseText = await res.text();
                    console.log('[getOrCreateChat] Response text:', responseText);
                    
                    const data = JSON.parse(responseText);
                    console.log('[getOrCreateChat] Parsed data:', data);
                    console.log('[getOrCreateChat] Returning chat_id:', data.chat_id);
                    
                    return data.chat_id;
                } catch (err) {
                    console.error('[getOrCreateChat] ERROR:', err);
                    console.error('[getOrCreateChat] Full error details:', err.message, err.stack);
                    return null;
                }
            },

            loadConversation: async function(chatId) {
                console.log('[loadConversation] START - chatId:', chatId);
                try {
                    const res = await fetch(`../backend/chat/fetch_messages.php?chat_id=${chatId}`, {
                        credentials: 'same-origin'
                    });

                    console.log('[loadConversation] Response status:', res.status);

                    if (!res.ok) {
                        const errorText = await res.text();
                        console.error('[loadConversation] Response not OK:', errorText);
                        throw new Error('Cannot load messages');
                    }

                    const data = await res.json();
                    console.log('[loadConversation] Data received:', data);
                    
                    // Extract messages array from response
                    const messages = data.messages || data || [];
                    console.log('[loadConversation] Messages array:', messages, 'length:', messages.length);
                    
                    if (!Array.isArray(messages)) {
                        console.error('[loadConversation] Messages is not an array:', typeof messages);
                        throw new Error('Invalid messages format');
                    }

                    const container = document.getElementById('conversationMessages');
                    container.innerHTML = '';

                    const currentUserId = JSON.parse(localStorage.getItem('user'))?.id;
                    console.log('[loadConversation] Current user ID:', currentUserId);

                    messages.forEach((msg) => {
                        const isSent = msg.sender_id == currentUserId;
                        console.log('[loadConversation] Displaying message:', msg.id, 'from:', msg.sender_id, 'isSent:', isSent);
                        this.displayMessage(msg.sender_id, msg.content, msg.timestamp, isSent ? 'sent' : 'received');
                    });

                    this.scrollToBottom();
                    console.log('[loadConversation] COMPLETE - displayed', messages.length, 'messages');
                } catch (err) {
                    console.error('[loadConversation] ERROR:', err);
                    console.error('[loadConversation] Full error:', err.message, err.stack);
                }
            },

            sendMessage: async function() {
                const input = document.getElementById('messageText');
                const text = input.value.trim();

                if (!text || !this.currentChatId) return;

                const user = JSON.parse(localStorage.getItem('user'));
                const senderId = user?.id;

                try {
                    const res = await fetch('../backend/chat/send_message.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        credentials: 'same-origin',
                        body: JSON.stringify({
                            chat_id: this.currentChatId,
                            content: text
                        })
                    });

                    if (!res.ok) throw new Error('Failed to save message');

                    this.displayMessage(senderId, text, new Date().toISOString(), 'sent');

                    if (this.socket?.connected) {
                        this.socket.emit('chat message', {
                            chatId: this.currentChatId,
                            message: text
                        });
                    }

                    input.value = '';
                    input.focus();
                    this.scrollToBottom();
                } catch (err) {
                    console.error('Send message failed:', err);
                    alert('Could not send message. Try again.');
                }
            },

            displayMessage: function(senderId, text, timestamp, type) {
                const container = document.getElementById('conversationMessages');

                const div = document.createElement('div');
                div.className = `message ${type}`;

                const time = new Date(timestamp).toLocaleTimeString([], {
                    hour: '2-digit',
                    minute: '2-digit'
                });

                div.innerHTML = `
                    <div class="message-content">
                        <div class="message-text">${text}</div>
                        <div class="message-time">${time}</div>
                    </div>
                `;

                container.appendChild(div);
                this.scrollToBottom();
            },

            showMessageInput: function() {
                const inputArea = document.getElementById('messageInput');
                if (inputArea) {
                    inputArea.style.display = 'block';
                }
            },

            scrollToBottom: function() {
                const container = document.getElementById('messagesContainer');
                if (container) {
                    container.scrollTop = container.scrollHeight;
                }
            }
        };

        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded → initializing chat');
            chatApp.init();
        });
    </script>
</body>
</html>