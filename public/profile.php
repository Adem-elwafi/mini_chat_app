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
    <title>MiniChatApp - Profile</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <div class="profile-container">
            <div class="profile-header">
                <div class="profile-avatar" id="profileAvatar"></div>
                <div class="profile-info">
                    <h2 id="profileName"></h2>
                    <div class="email" id="profileEmail"></div>
                </div>
            </div>
            
            <div class="error-message" id="errorMessage" style="display: none;"></div>
            <div class="success-message" id="successMessage" style="display: none;"></div>
            
            <form class="profile-form" id="profileForm">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" required>
                </div>
                
                <div class="form-group">
                    <label for="profile_info">Profile Info</label>
                    <textarea id="profile_info" placeholder="Tell others about yourself"></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn">Update Profile</button>
                    <button type="button" class="btn btn-secondary" onclick="window.location.href='chat.php'">Back to Chat</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Load user data from localStorage
        const user = JSON.parse(localStorage.getItem('user'));
        
        if (user) {
            document.getElementById('profileName').textContent = user.username;
            document.getElementById('profileEmail').textContent = user.email;
            document.getElementById('profileAvatar').textContent = user.username.charAt(0).toUpperCase();
            document.getElementById('username').value = user.username;
            document.getElementById('email').value = user.email;
            document.getElementById('profile_info').value = user.profile_info || '';
        }
        
        // Handle form submission
        document.getElementById('profileForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const username = document.getElementById('username').value.trim();
            const email = document.getElementById('email').value.trim();
            const profileInfo = document.getElementById('profile_info').value.trim();
            
            // Clear previous messages
            document.getElementById('errorMessage').style.display = 'none';
            document.getElementById('successMessage').style.display = 'none';
            
            try {
                const response = await fetch('../backend/auth/updateProfile.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        username,
                        email,
                        profile_info: profileInfo
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Update localStorage
                    localStorage.setItem('user', JSON.stringify(data.user));
                    
                    // Update UI
                    document.getElementById('profileName').textContent = data.user.username;
                    document.getElementById('profileEmail').textContent = data.user.email;
                    document.getElementById('profileAvatar').textContent = data.user.username.charAt(0).toUpperCase();
                    
                    // Show success message
                    document.getElementById('successMessage').textContent = data.message;
                    document.getElementById('successMessage').style.display = 'block';
                } else {
                    document.getElementById('errorMessage').textContent = data.error;
                    document.getElementById('errorMessage').style.display = 'block';
                }
            } catch (error) {
                document.getElementById('errorMessage').textContent = 'Network error. Please try again.';
                document.getElementById('errorMessage').style.display = 'block';
                console.error('Profile update error:', error);
            }
        });
    </script>
</body>
</html>