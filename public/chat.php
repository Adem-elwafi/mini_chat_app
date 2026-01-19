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
        // Chat app functionality
        const chatApp = {
            currentChatUserId: null,
            
            init: function() {
                console.log('Chat app initializing...');
                
                // Initialize user info from localStorage
                try {
                    const user = JSON.parse(localStorage.getItem('user'));
                    if (user) {
                        console.log('User found in localStorage:', user);
                        document.getElementById('userName').textContent = user.username || 'User';
                        document.getElementById('userEmail').textContent = user.email || 'user@example.com';
                        document.getElementById('userAvatar').textContent = (user.username || 'U').charAt(0).toUpperCase();
                    } else {
                        console.log('No user found in localStorage, using default');
                        // Create a default user if none exists
                        const defaultUser = {
                            username: "John Doe",
                            email: "john@example.com"
                        };
                        localStorage.setItem('user', JSON.stringify(defaultUser));
                        document.getElementById('userName').textContent = defaultUser.username;
                        document.getElementById('userEmail').textContent = defaultUser.email;
                        document.getElementById('userAvatar').textContent = defaultUser.username.charAt(0).toUpperCase();
                    }
                } catch (error) {
                    console.error('Error loading user from localStorage:', error);
                }
                
                // Set up event listeners
                this.setupEventListeners();
                
                // Add to global scope
                window.chatApp = this;
                
                console.log('Chat app initialized successfully');
            },
            
            setupEventListeners: function() {
                console.log('Setting up event listeners...');
                
                // User selection
                const userItems = document.querySelectorAll('.user-item');
                console.log('Found user items:', userItems.length);
                
                userItems.forEach((item) => {
                    item.addEventListener('click', (event) => {
                        const userId = item.getAttribute('data-user-id');
                        console.log('User clicked:', userId);
                        this.selectUser(userId, item);
                    });
                });
                
                // Logout button
                const logoutBtn = document.getElementById('logoutBtn');
                if (logoutBtn) {
                    logoutBtn.addEventListener('click', async () => {
                        if (!confirm('Are you sure you want to logout?')) {
                            return;
                        }

                        try {
                            // Call PHP logout to destroy the session on server
                            const response = await fetch('../backend/auth/logout.php', {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json'
                                },
                                credentials: 'same-origin'
                            });

                            if (!response.ok) {
                                throw new Error('Network error during logout');
                            }

                            const data = await response.json();
                            if (data && data.success) {
                                // Optionally clear local storage copy
                                localStorage.removeItem('user');
                                // Redirect to login/index page
                                window.location.href = 'index.php';
                            } else {
                                throw new Error(data?.message || 'Logout failed');
                            }
                        } catch (err) {
                            console.error('Logout failed:', err);
                            alert('Logout failed. Please try again.');
                        }
                    });
                }
                
                // Profile button
                const profileBtn = document.getElementById('profileBtn');
                if (profileBtn) {
                    profileBtn.addEventListener('click', () => {
                        // Navigate to the server-rendered profile page (session-protected)
                        window.location.href = 'profile.php';
                    });
                }
                
                // Message form submission
                const messageForm = document.getElementById('messageForm');
                if (messageForm) {
                    messageForm.addEventListener('submit', (e) => {
                        e.preventDefault();
                        this.sendMessage();
                    });
                }
                
                console.log('Event listeners setup complete');
            },
            
            selectUser: function(userId, userElement) {
                console.log('Selecting user:', userId);
                
                // Remove active class from all users
                const userItems = document.querySelectorAll('.user-item');
                userItems.forEach(item => {
                    item.classList.remove('active');
                });
                
                // Add active class to selected user
                if (userElement) {
                    userElement.classList.add('active');
                }
                
                // Get user info from the clicked element
                const userName = userElement.querySelector('.fw-bold').textContent;
                const userStatus = userElement.querySelector('.text-muted').textContent;
                const userAvatar = userElement.querySelector('.user-avatar').textContent;
                
                console.log('User info:', { userName, userStatus, userAvatar });
                
                // Update chat header
                document.getElementById('defaultHeader').style.display = 'none';
                document.getElementById('selectedUserInfo').style.display = 'flex';
                document.getElementById('selectedUserName').textContent = userName;
                document.getElementById('selectedUserStatus').textContent = userStatus;
                document.getElementById('selectedUserAvatar').textContent = userAvatar;
                
                // Hide empty state and clear previous messages
                document.getElementById('emptyState').style.display = 'none';
                
                // Clear previous conversation
                const conversationMessages = document.getElementById('conversationMessages');
                conversationMessages.innerHTML = '';
                conversationMessages.style.display = 'block';
                
                // Load conversation for this user
                this.loadConversation(userId, userName);
                
                // Show message input
                this.showMessageInput();
                
                // Set current chat user ID
                this.currentChatUserId = userId;
                
                console.log('User selection complete');
            },
            
            loadConversation: function(userId, userName) {
                console.log('Loading conversation for user:', userId);
                
                const conversationMessages = document.getElementById('conversationMessages');
                
                // Sample conversation data based on user
                const sampleMessages = {
                    '1': [
                        { type: 'received', text: 'Hi there! This is your own account.', time: '10:00 AM' },
                        { type: 'sent', text: 'Yes, this is a chat with yourself for testing.', time: '10:02 AM' }
                    ],
                    '2': [
                        { type: 'received', text: 'Hi! How are you doing today?', time: '09:30 AM' },
                        { type: 'sent', text: "I'm doing great, thanks! Just working on a new project.", time: '09:32 AM' },
                        { type: 'received', text: 'That sounds interesting. What kind of project?', time: '09:35 AM' },
                        { type: 'sent', text: "It's a chat application with a responsive design using Bootstrap.", time: '09:37 AM' }
                    ],
                    '3': [
                        { type: 'received', text: 'Hey! Are we still meeting tomorrow?', time: 'Yesterday' },
                        { type: 'sent', text: 'Yes, 2 PM at the usual place.', time: 'Yesterday' }
                    ],
                    '4': [
                        { type: 'sent', text: 'Can you send me the project files?', time: '2 days ago' },
                        { type: 'received', text: 'Sure, I\'ll email them to you shortly.', time: '2 days ago' }
                    ],
                    '5': [
                        { type: 'received', text: 'Thanks for your help with the presentation!', time: '3 days ago' },
                        { type: 'sent', text: 'No problem, happy to help!', time: '3 days ago' }
                    ]
                };
                
                // Get messages for this user or use default
                const messages = sampleMessages[userId] || [
                    { type: 'received', text: 'Hello! This is the start of your conversation.', time: 'Just now' }
                ];
                
                // Add messages to conversation
                messages.forEach(msg => {
                    const messageDiv = document.createElement('div');
                    messageDiv.className = `message ${msg.type}`;
                    
                    messageDiv.innerHTML = `
                        <div class="message-content">
                            <div class="message-text">${msg.text}</div>
                            <div class="message-time">${msg.time}</div>
                        </div>
                    `;
                    
                    conversationMessages.appendChild(messageDiv);
                });
                
                // Scroll to bottom
                this.scrollToBottom();
                
                console.log('Conversation loaded with', messages.length, 'messages');
            },
            
            showMessageInput: function() {
                const messageInput = document.getElementById('messageInput');
                if (messageInput) {
                    messageInput.style.display = 'block';
                }
            },
            
            hideMessageInput: function() {
                const messageInput = document.getElementById('messageInput');
                if (messageInput) {
                    messageInput.style.display = 'none';
                }
            },
            
            sendMessage: function() {
                const messageInput = document.getElementById('messageText');
                const messageText = messageInput.value.trim();
                
                if (messageText === '') {
                    console.log('Message is empty, not sending');
                    return;
                }
                
                console.log('Sending message:', messageText);
                
                // Create new message element
                const messageDiv = document.createElement('div');
                messageDiv.className = 'message sent';
                
                const now = new Date();
                const timeString = now.getHours().toString().padStart(2, '0') + ':' + 
                                 now.getMinutes().toString().padStart(2, '0');
                
                messageDiv.innerHTML = `
                    <div class="message-content">
                        <div class="message-text">${messageText}</div>
                        <div class="message-time">${timeString}</div>
                    </div>
                `;
                
                // Add to conversation
                const conversationMessages = document.getElementById('conversationMessages');
                if (conversationMessages) {
                    conversationMessages.appendChild(messageDiv);
                }
                
                // Clear input
                messageInput.value = '';
                messageInput.focus();
                
                // Scroll to bottom
                this.scrollToBottom();
                
                // Simulate a reply after a short delay (only if not chatting with yourself)
                if (this.currentChatUserId !== '1') {
                    setTimeout(() => {
                        this.simulateReply();
                    }, 1000);
                }
            },
            
            simulateReply: function() {
                console.log('Simulating reply...');
                
                const replies = [
                    "Thanks for your message!",
                    "That's interesting, tell me more.",
                    "I'll think about that and get back to you.",
                    "Sounds good to me!",
                    "Let's discuss this tomorrow.",
                    "Got it, thanks!",
                    "I agree with you on that.",
                    "Can we talk about this later?"
                ];
                
                const randomReply = replies[Math.floor(Math.random() * replies.length)];
                
                // Create reply message element
                const messageDiv = document.createElement('div');
                messageDiv.className = 'message received';
                
                const now = new Date();
                const timeString = now.getHours().toString().padStart(2, '0') + ':' + 
                                 now.getMinutes().toString().padStart(2, '0');
                
                messageDiv.innerHTML = `
                    <div class="message-content">
                        <div class="message-text">${randomReply}</div>
                        <div class="message-time">${timeString}</div>
                    </div>
                `;
                
                // Add to conversation
                const conversationMessages = document.getElementById('conversationMessages');
                if (conversationMessages) {
                    conversationMessages.appendChild(messageDiv);
                }
                
                // Scroll to bottom
                this.scrollToBottom();
            },
            
            scrollToBottom: function() {
                const container = document.getElementById('messagesContainer');
                if (container) {
                    container.scrollTop = container.scrollHeight;
                }
            }
        };
        
        // Initialize the chat app when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, initializing chat app...');
            chatApp.init();
        });
    </script>
</body>
</html>