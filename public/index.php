<?php // la partie php qui vèrifie si l'utilisateur dèja cnct ou nn : 
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: chat.php');
    exit();
}
?>
<!DOCTYPE html> 
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MiniChatApp - Login</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container auth-container">
        <div class="auth-form" id="loginForm">
            <h2>Welcome to MiniChatApp</h2>
            <div class="error-message" id="errorMessage" style="display: none;"></div>
            <div class="success-message" id="successMessage" style="display: none;"></div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" placeholder="Enter your email">
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" placeholder="Enter your password">
            </div>
            
            <button class="btn" onclick="login()">Login</button>
            
            <div class="form-footer">
                Don't have an account? <a href="#" onclick="showRegister()">Register here</a>
            </div> 
        </div>

        <div class="auth-form" id="registerForm" style="display: none;">
            <h2>Create Account</h2>
            
            <div class="form-group">
                <label for="regUsername">Username</label>
                <input type="text" id="regUsername" placeholder="Choose a username">
            </div>
            
            <div class="form-group">
                <label for="regEmail">Email</label>
                <input type="email" id="regEmail" placeholder="Enter your email">
            </div>
            
            <div class="form-group">
                <label for="regPassword">Password</label>
                <input type="password" id="regPassword" placeholder="At least 6 characters">
            </div>
            
            <div class="form-group">
                <label for="regProfile">Profile Info (Optional)</label>
                <textarea id="regProfile" placeholder="Tell others about yourself"></textarea>
            </div>
            
            <button class="btn" onclick="register()">Register</button>
            
            <div class="form-footer">
                Already have an account? <a href="#" onclick="showLogin()">Login here</a>
            </div>
        </div>
    </div>

    <script>
        // page d'Inscription : 
        function showRegister() {
            document.getElementById('loginForm').style.display = 'none';
            document.getElementById('registerForm').style.display = 'block';
            clearMessages();
        }
        // page de connexion :
        function showLogin() {
            document.getElementById('registerForm').style.display = 'none';
            document.getElementById('loginForm').style.display = 'block';
            clearMessages();
        }
        // erreur : connexion èchouè :
        function showError(message) {
            const errorDiv = document.getElementById('errorMessage');
            errorDiv.textContent = message;
            errorDiv.style.display = 'block';
            document.getElementById('successMessage').style.display = 'none';
        }
        // coonexion validè : 
        function showSuccess(message) {
            const successDiv = document.getElementById('successMessage');
            successDiv.textContent = message;
            successDiv.style.display = 'block';
            document.getElementById('errorMessage').style.display = 'none';
        }
        // suppression de messages : 
        function clearMessages() {
            document.getElementById('errorMessage').style.display = 'none';
            document.getElementById('successMessage').style.display = 'none';
        }
        // connecter :
        async function login() {
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value.trim();
            
            if (!email || !password) {
                showError('Please fill in all fields');
                return;
            }
            
            try {
                const response = await fetch('../backend/auth/login.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ email, password })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Store user data in localStorage
                    localStorage.setItem('user', JSON.stringify(data.user));
                    // Redirect to chat
                    window.location.href = 'chat.php';
                } else {
                    showError(data.error || 'Login failed');
                }
            } catch (error) {
                showError('Network error. Please try again.');
                console.error('Login error:', error);
            }
        }
        
        async function register() {
            const username = document.getElementById('regUsername').value.trim();
            const email = document.getElementById('regEmail').value.trim();
            const password = document.getElementById('regPassword').value.trim();
            const profileInfo = document.getElementById('regProfile').value.trim();
            
            // Basic validation
            if (!username || !email || !password) {
                showError('Please fill in all required fields');
                return;
            }
            
            if (password.length < 6) {
                showError('Password must be at least 6 characters');
                return;
            }
            
            if (!validateEmail(email)) {
                showError('Please enter a valid email address');
                return;
            }
            
            try {
                const response = await fetch('../backend/auth/register.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        username,
                        email,
                        password,
                        profile_info: profileInfo
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Store user data in localStorage
                    localStorage.setItem('user', JSON.stringify(data.user));
                    // Show success message
                    showSuccess('Registration successful! Redirecting to chat...');
                    // Redirect after 2 seconds
                    setTimeout(() => {
                        window.location.href = 'chat.php';
                    }, 2000);
                } else {
                    if (data.errors) {
                        showError(data.errors.join(', '));
                    } else {
                        showError(data.error || 'Registration failed');
                    }
                }
            } catch (error) {
                showError('Network error. Please try again.');
                console.error('Registration error:', error);
            }
        }
        
        function validateEmail(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        }
        
        // Allow pressing Enter to submit forms
        document.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const activeForm = document.getElementById('loginForm').style.display !== 'none' 
                    ? 'loginForm' 
                    : 'registerForm';
                
                if (activeForm === 'loginForm') {
                    login();
                } else {
                    register();
                }
            }
        });
        
        // Clear messages when user starts typing
        document.querySelectorAll('input, textarea').forEach(element => {
            element.addEventListener('input', clearMessages);
        });
    </script>
</body>
</html>