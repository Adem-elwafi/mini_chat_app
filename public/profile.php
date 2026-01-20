<?php
require_once '../backend/config/db.php';

if (!isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$pdo = Database::getInstance();
$stmt = $pdo->prepare('SELECT id, username, email, profile_info, created_at FROM users WHERE id = ?');
$stmt->execute([getUserID()]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: ../backend/auth/logout.php');
    exit();
}

$joinDate = $user['created_at'] ? date('M d, Y', strtotime($user['created_at'])) : '';
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
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <i class="bi bi-chat-dots-fill" aria-hidden="true"></i>
                    <span>MiniChatApp</span>
                </div>
            </div>

            <ul class="nav-menu">
                <li>
                    <a href="chat.php">
                        <i class="bi bi-house-door" aria-hidden="true"></i>
                        <span>Chat</span>
                    </a>
                </li>
                <li class="active">
                    <a href="profile.php">
                        <i class="bi bi-person-circle" aria-hidden="true"></i>
                        <span>Profile</span>
                    </a>
                </li>
                <li>
                    <a href="../backend/auth/logout.php">
                        <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                        <span>Logout</span>
                    </a>
                </li>
            </ul>

            <div class="sidebar-footer">
                <div class="user-mini-profile">
                    <div class="user-avatar-mini" data-avatar-initial><?= htmlspecialchars(strtoupper(substr($user['username'], 0, 1))) ?></div>
                    <div class="user-info-mini">
                        <span id="miniName"><?= htmlspecialchars($user['username']) ?></span>
                        <small id="miniEmail"><?= htmlspecialchars($user['email']) ?></small>
                    </div>
                </div>
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
</body>
</html>