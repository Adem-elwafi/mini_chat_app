<?php
require_once '../backend/config/db.php';

// Check if user is logged in - session is already started by db.php
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$pdo = Database::getInstance();

// Get user ID from session
$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare('SELECT id, username, email, profile_info, created_at FROM users WHERE id = ?');
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        // User not found in database
        session_destroy();
        header('Location: index.php');
        exit();
    }
    
    $joinDate = $user['created_at'] ? date('M d, Y', strtotime($user['created_at'])) : 'Unknown date';
    
} catch (PDOException $e) {
    // Database error
    error_log("Database error in profile.php: " . $e->getMessage());
    die("Database error. Please try again later.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MiniChatApp - Profile</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">MiniChatApp</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="chat.php">Chat</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="profile.php">Profile</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" id="logoutNav">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="profile-page-container">
        <main class="main-content">
            <header class="main-header">
                <h1><i class="bi bi-person-lines-fill me-2"></i> Profile</h1>
            </header>

            <section class="profile-section">
                <div class="alert alert-error" id="errorAlert" role="alert" style="display:none;"></div>
                <div class="alert alert-success" id="successAlert" role="status" style="display:none;"></div>

                <div class="profile-card">
                    <div class="profile-header-card">
                        <div class="profile-avatar">
                            <?= htmlspecialchars(strtoupper(substr($user['username'], 0, 1))) ?>
                        </div>
                        <div class="profile-info-header">
                            <h2><?= htmlspecialchars($user['username']) ?></h2>
                            <div class="profile-email"><?= htmlspecialchars($user['email']) ?></div>
                            <div class="profile-meta">
                                <div class="meta-item">
                                    <i class="bi bi-calendar-event"></i>
                                    <span>Joined <?= htmlspecialchars($joinDate) ?></span>
                                </div>
                                <div class="meta-item">
                                    <i class="bi bi-shield-check"></i>
                                    <span>Account active</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-section">
                        <h3><i class="bi bi-gear me-2"></i> Profile details</h3>

                        <form class="profile-form" id="profileForm" autocomplete="off">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="username">Username <span class="required">*</span></label>
                                    <input type="text" id="username" name="username" class="form-input" 
                                           required minlength="3" maxlength="50" 
                                           value="<?= htmlspecialchars($user['username']) ?>">
                                    <div class="form-helper">3-50 characters, letters and numbers.</div>
                                </div>

                                <div class="form-group">
                                    <label for="email">Email <span class="required">*</span></label>
                                    <input type="email" id="email" name="email" class="form-input" 
                                           required value="<?= htmlspecialchars($user['email']) ?>">
                                </div>

                                <div class="form-group full-width">
                                    <label for="profileInfo">Bio</label>
                                    <textarea id="profileInfo" name="profile_info" class="form-textarea" 
                                              maxlength="240" placeholder="Share a short bio..."><?= htmlspecialchars($user['profile_info'] ?? '') ?></textarea>
                                    <div class="char-counter" id="bioCounter">0/240</div>
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="button" class="btn btn-secondary" id="resetBtn">Reset</button>
                                <button type="submit" class="btn btn-primary">Save changes</button>
                            </div>
                        </form>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <!-- Bootstrap JS for navbar collapse -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        window.profileUser = <?= json_encode($user, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
        
        document.addEventListener('DOMContentLoaded', () => {
            // Logout handler
            const logoutNav = document.getElementById('logoutNav');
            if (logoutNav) {
                logoutNav.addEventListener('click', async (e) => {
                    e.preventDefault();
                    try {
                        await fetch('../backend/auth/logout.php', { credentials: 'same-origin' });
                    } catch (err) {
                        console.error('Logout failed:', err);
                    }
                    window.location.href = 'index.php';
                });
            }

            // Profile form handling
            const form = document.getElementById('profileForm');
            const bioCounter = document.getElementById('bioCounter');
            const bioTextarea = document.getElementById('profileInfo');
            const errorAlert = document.getElementById('errorAlert');
            const successAlert = document.getElementById('successAlert');
            const resetBtn = document.getElementById('resetBtn');

            // Update character counter
            function updateCounter() {
                const length = bioTextarea.value.length;
                bioCounter.textContent = `${length}/240`;
            }
            
            if (bioTextarea) {
                bioTextarea.addEventListener('input', updateCounter);
                updateCounter();
            }

            // Reset form
            if (resetBtn) {
                resetBtn.addEventListener('click', () => {
                    form.reset();
                    updateCounter();
                    hideAlerts();
                });
            }

            // Form submission
            if (form) {
                form.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    hideAlerts();

                    const formData = {
                        username: document.getElementById('username').value.trim(),
                        email: document.getElementById('email').value.trim(),
                        profile_info: document.getElementById('profileInfo').value.trim()
                    };

                    try {
                        const response = await fetch('../backend/auth/updateProfile.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            credentials: 'same-origin',
                            body: JSON.stringify(formData)
                        });

                        const data = await response.json();

                        if (data.success) {
                            showSuccess('Profile updated successfully!');
                            // Update stored user data
                            window.profileUser = { ...window.profileUser, ...formData };
                        } else {
                            showError(data.error || 'Failed to update profile');
                        }
                    } catch (error) {
                        showError('Network error. Please try again.');
                        console.error('Update error:', error);
                    }
                });
            }

            function showError(message) {
                errorAlert.textContent = message;
                errorAlert.style.display = 'block';
                successAlert.style.display = 'none';
            }

            function showSuccess(message) {
                successAlert.textContent = message;
                successAlert.style.display = 'block';
                errorAlert.style.display = 'none';
            }

            function hideAlerts() {
                errorAlert.style.display = 'none';
                successAlert.style.display = 'none';
            }
        });
    </script>
</body>
</html>