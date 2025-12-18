<?php
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
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <div class="chat-layout">
            <!-- Users Sidebar -->
            <div class="users-sidebar">
                <div class="user-header">
                    <div class="current-user">
                        <div class="avatar" id="userAvatar"></div>
                        <div class="user-info">
                            <h3 id="userName"></h3>
                            <div class="email" id="userEmail"></div>
                        </div>
                    </div>
                </div>
                
                <div class="users-list" id="usersList">
                    <div class="loading">Loading users...</div>
                </div>
                
                <div class="sidebar-footer">
                    <button class="btn btn-secondary" id="profileBtn" style="margin: 20px;">My Profile</button>
                    <button class="btn btn-secondary" id="logoutBtn" style="margin: 0 20px 20px;">Logout</button>
                </div>
            </div>
            
            <!-- Chat Area -->
            <div class="chat-area">
                <div class="chat-header">
                    <h3>Select a user to start chatting</h3>
                </div>
                
                <div class="messages-container" id="messagesContainer">
                    <div class="empty-state">
                        <div>💬</div>
                        <p>Select a user from the sidebar to start a conversation</p>
                    </div>
                </div>
                
                <div class="message-input" style="display: none;" id="messageInput">
                    <form>
                        <input type="text" placeholder="Type your message..." id="messageText">
                        <button type="submit" class="btn">Send</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="js/chat.js"></script>
    <script>
        // Initialize when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            if (window.chatApp) {
                // Update UI with current user info
                const user = JSON.parse(localStorage.getItem('user'));
                if (user) {
                    document.getElementById('userName').textContent = user.username;
                    document.getElementById('userEmail').textContent = user.email;
                    document.getElementById('userAvatar').textContent = user.username.charAt(0).toUpperCase();
                }
                
                // Show message input when chat is selected
                window.chatApp.showMessageInput = function() {
                    document.getElementById('messageInput').style.display = 'block';
                };
                
                // Hide message input when no chat
                window.chatApp.hideMessageInput = function() {
                    document.getElementById('messageInput').style.display = 'none';
                };
            }
        });
    </script>
</body>
</html>