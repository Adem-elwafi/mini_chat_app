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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/profile.css">
</head>
<body>
    <div class="app-container">
        <aside class="users-sidebar">
            <div class="user-header">
                <div class="current-user">
                    <div class="avatar" data-avatar-initial>
                        <?= htmlspecialchars(strtoupper(substr($user['username'], 0, 1))) ?>
                    </div>
                    <div class="user-info">
                        <h3 id="miniName"><?= htmlspecialchars($user['username']) ?></h3>
                        <div class="email" id="miniEmail"><?= htmlspecialchars($user['email']) ?></div>
                    </div>
                </div>
            </div>
            
            <div class="users-nav">
                <a href="chat.php" class="nav-item">
                    <i class="bi bi-chat-dots-fill"></i>
                    <div class="nav-text">
                        <div>Chat</div>
                        <small class="text-muted">Real-time messaging</small>
                    </div>
                </a>
                <a href="profile.php" class="nav-item active">
                    <i class="bi bi-person-circle"></i>
                    <div class="nav-text">
                        <div>Profile</div>
                        <small class="text-muted">Manage your account</small>
                    </div>
                </a>
            </div>
            
            <div class="sidebar-footer">
                <button class="btn btn-profile" onclick="window.location.href='profile.php'">
                    <i class="bi bi-person-circle me-2"></i>My Profile
                </button>
                <button class="btn btn-logout" onclick="window.location.href='../backend/auth/logout.php'">
                    <i class="bi bi-box-arrow-right me-2"></i>Logout
                </button>
            </div>
        </aside>

        <main class="main-content">
            <header class="main-header">
                <h1><i class="bi bi-person-lines-fill" aria-hidden="true"></i> Profile</h1>
            </header>

            <section class="profile-container">
                <div class="alert alert-error" id="errorAlert" role="alert"></div>
                <div class="alert alert-success" id="successAlert" role="status"></div>

                <div class="profile-card">
                    <div class="profile-header-card">
                        <div class="profile-avatar" data-avatar-initial>
                            <?= htmlspecialchars(strtoupper(substr($user['username'], 0, 1))) ?>
                            <span class="avatar-status" aria-hidden="true"></span>
                        </div>
                        <div class="profile-info-header">
                            <h2 id="displayName"><?= htmlspecialchars($user['username']) ?></h2>
                            <div class="profile-email" id="displayEmail"><?= htmlspecialchars($user['email']) ?></div>
                            <div class="profile-meta">
                                <div class="meta-item">
                                    <i class="bi bi-calendar-event" aria-hidden="true"></i>
                                    <span>Joined <?= htmlspecialchars($joinDate) ?></span>
                                </div>
                                <div class="meta-item">
                                    <i class="bi bi-shield-check" aria-hidden="true"></i>
                                    <span>Account active</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-header">
                        <h3><i class="bi bi-gear" aria-hidden="true"></i> Profile details</h3>
                    </div>

                    <form class="profile-form" id="profileForm" autocomplete="off">
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label" for="username">
                                    <i class="bi bi-person" aria-hidden="true"></i>
                                    Username <span class="required">*</span>
                                </label>
                                <input type="text" id="username" name="username" class="form-input" required minlength="3" maxlength="50" value="<?= htmlspecialchars($user['username']) ?>">
                                <div class="form-helper">3-50 characters, letters and numbers.</div>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="email">
                                    <i class="bi bi-envelope" aria-hidden="true"></i>
                                    Email <span class="required">*</span>
                                </label>
                                <input type="email" id="email" name="email" class="form-input" required value="<?= htmlspecialchars($user['email']) ?>">
                            </div>

                            <div class="form-group full-width">
                                <label class="form-label" for="profileInfo">
                                    <i class="bi bi-card-text" aria-hidden="true"></i>
                                    Bio
                                </label>
                                <textarea id="profileInfo" name="profile_info" class="form-textarea" maxlength="240" placeholder="Share a short bio..."><?= htmlspecialchars($user['profile_info'] ?? '') ?></textarea>
                                <div class="char-counter" id="bioCounter">0/240</div>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="button" class="btn btn-secondary" id="resetBtn">
                                <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>
                                Reset
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check2-circle" aria-hidden="true"></i>
                                Save changes
                            </button>
                        </div>
                    </form>
                </div>
            </section>
        </main>
    </div>

    <script>
        window.profileUser = <?= json_encode($user, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    </script>
    <script src="js/profile.js"></script>
    
    <!-- Mobile sidebar elements -->
    <button class="sidebar-toggle" id="sidebarToggle">
        <i class="bi bi-list"></i>
    </button>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <div class="mobile-back-btn" id="mobileBackBtn">
        <i class="bi bi-arrow-left"></i>
    </div>
</body>
</html>