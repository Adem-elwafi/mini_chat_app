class ChatApp {
    constructor() {
        this.currentUser = null;
        this.currentChat = null;
        this.users = [];
        this.messages = [];
        this.pollingInterval = null;
        
        this.init();
    }
    
    async init() {
        await this.checkAuth();
        await this.loadCurrentUser();
        
        if (this.currentUser) {
            await this.loadUsers();
            this.setupEventListeners();
            this.startPolling();
        }
    }
    
    async checkAuth() {
        const userData = localStorage.getItem('user');
        if (!userData) {
            window.location.href = 'index.php';
            return;
        }
        this.currentUser = JSON.parse(userData);
    }
    
    async loadCurrentUser() {
        try {
            const response = await fetch('../backend/auth/updateProfile.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    username: this.currentUser.username,
                    email: this.currentUser.email,
                    profile_info: this.currentUser.profile_info || ''
                })
            });
            
            const data = await response.json();
            if (data.success) {
                this.currentUser = data.user;
                localStorage.setItem('user', JSON.stringify(data.user));
                this.updateUI();
            }
        } catch (error) {
            console.error('Error loading user:', error);
        }
    }
    
    async loadUsers() {
        try {
            const response = await fetch('../backend/chat/fetch_users.php');
            const data = await response.json();
            
            if (data.success) {
                this.users = data.users;
                this.renderUsersList();
            }
        } catch (error) {
            console.error('Error loading users:', error);
        }
    }
    
    async startChat(userId) {
        try {
            const response = await fetch('../backend/chat/start_chat.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ user_id: userId })
            });
            
            const data = await response.json();
            if (data.success) {
                this.currentChat = data.chat;
                await this.loadMessages();
                this.updateChatHeader();
                this.highlightSelectedUser(userId);
                this.showMessageInput();
            }
        } catch (error) {
            console.error('Error starting chat:', error);
        }
    }
    
    async loadMessages() {
        if (!this.currentChat) return;
        
        try {
            const response = await fetch(`../backend/chat/fetch_messages.php?chat_id=${this.currentChat.id}`);
            const data = await response.json();
            
            if (data.success) {
                this.messages = data.messages;
                this.renderMessages();
            }
        } catch (error) {
            console.error('Error loading messages:', error);
        }
    }
    
    async sendMessage(content) {
        if (!content.trim() || !this.currentChat) return;
        
        try {
            const response = await fetch('../backend/chat/send_message.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    chat_id: this.currentChat.id,
                    content: content
                })
            });
            
            const data = await response.json();
            if (data.success) {
                await this.loadMessages();
                this.clearMessageInput();
            }
        } catch (error) {
            console.error('Error sending message:', error);
        }
    }
    
    async logout() {
        try {
            await fetch('../backend/auth/logout.php');
            localStorage.removeItem('user');
            window.location.href = 'index.php';
        } catch (error) {
            console.error('Error logging out:', error);
        }
    }
    
    updateUI() {
        // Update current user display
        const userAvatar = document.querySelector('.avatar');
        const userName = document.querySelector('.user-info h3');
        const userEmail = document.querySelector('.user-info .email');
        
        if (userAvatar) {
            userAvatar.textContent = this.currentUser.username.charAt(0).toUpperCase();
        }
        if (userName) {
            userName.textContent = this.currentUser.username;
        }
        if (userEmail) {
            userEmail.textContent = this.currentUser.email;
        }
    }
    
    renderUsersList() {
        const usersList = document.getElementById('usersList');
        if (!usersList) return;
        
        usersList.innerHTML = this.users.map(user => `
            <div class="user-item" data-user-id="${user.id}" onclick="chatApp.startChat(${user.id})">
                <div class="avatar">${user.username.charAt(0).toUpperCase()}</div>
                <div>
                    <div class="user-name">${user.username}</div>
                    <div class="user-email">${user.email}</div>
                </div>
            </div>
        `).join('');
    }
    
    highlightSelectedUser(userId) {
        document.querySelectorAll('.user-item').forEach(item => {
            item.classList.remove('active');
            if (parseInt(item.dataset.userId) === userId) {
                item.classList.add('active');
            }
        });
    }
    
    renderMessages() {
        const messagesContainer = document.getElementById('messagesContainer');
        if (!messagesContainer) return;
        
        messagesContainer.innerHTML = this.messages.map(message => {
            const isSent = message.sender_id === this.currentUser.id;
            const senderName = isSent ? 'You' : message.sender_name;
            const time = new Date(message.timestamp).toLocaleTimeString([], { 
                hour: '2-digit', 
                minute: '2-digit' 
            });
            
            return `
                <div class="message ${isSent ? 'sent' : 'received'}">
                    <div class="message-sender">${senderName}</div>
                    <div class="message-bubble">${this.escapeHtml(message.content)}</div>
                    <div class="message-time">${time}</div>
                </div>
            `;
        }).join('');
        
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
    
    updateChatHeader() {
        if (!this.currentChat) return;
        
        const chatHeader = document.querySelector('.chat-header h3');
        const otherUser = this.currentChat.user1_id === this.currentUser.id ? 
            this.currentChat.user2_name : this.currentChat.user1_name;
        
        if (chatHeader) {
            chatHeader.textContent = `Chat with ${otherUser}`;
        }
    }
    
    clearMessageInput() {
        const input = document.querySelector('.message-input input');
        if (input) input.value = '';
    }
    
    showMessageInput() {
        const messageInput = document.getElementById('messageInput');
        if (messageInput) {
            messageInput.style.display = 'block';
        }
    }
    
    hideMessageInput() {
        const messageInput = document.getElementById('messageInput');
        if (messageInput) {
            messageInput.style.display = 'none';
        }
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    startPolling() {
        // Poll for new messages every 3 seconds
        this.pollingInterval = setInterval(() => {
            if (this.currentChat) {
                this.loadMessages();
            }
        }, 3000);
    }
    
    setupEventListeners() {
        // Message form submission
        const messageForm = document.querySelector('.message-input form');
        if (messageForm) {
            messageForm.addEventListener('submit', (e) => {
                e.preventDefault();
                const input = messageForm.querySelector('input');
                if (input.value.trim()) {
                    this.sendMessage(input.value.trim());
                }
            });
        }
        
        // Logout button
        const logoutBtn = document.getElementById('logoutBtn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', () => this.logout());
        }
        
        // Profile button
        const profileBtn = document.getElementById('profileBtn');
        if (profileBtn) {
            profileBtn.addEventListener('click', () => {
                window.location.href = 'profile.php';
            });
        }
    }
}

// Initialize chat app when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.chatApp = new ChatApp();
});