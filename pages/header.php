<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$authPages = ['login.php', 'register.php'];
$isAuthPage = in_array($currentPage, $authPages);

$unreadCount = 0;
$recentNotifications = [];
if (!$isAuthPage && isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/../classes/Notification.php';
    $notifModel = new Notification($db);
    $unreadCount = $notifModel->getUnreadCount($_SESSION['user_id']);
    $recentNotifications = $notifModel->getRecentForUser($_SESSION['user_id']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PurokInform | Announcement Platform</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --cream: #fdfaf3;
            --ink: #2e2a25;
            --muted: #7a7570;
            --pop-blue: #4a7fd6;
            --pop-blue-light: #eaf0fb;
            --pop-yellow: #f4c430;
            --pop-green: #5cb87c;
            --pop-pink: #ef7fa8;
        }
        body {
            background-color: var(--cream);
            color: var(--ink);
            font-family: 'Inter', sans-serif;
        }
        h1, h2, h3, h4, h5 {
            font-family: 'Playfair Display', serif;
            font-weight: 600;
        }
        .card {
            border: 1px solid rgba(0,0,0,0.06);
            border-radius: 10px;
            background-color: #ffffff;
        }
        .btn-warning {
            background-color: var(--pop-yellow);
            border-color: var(--pop-yellow);
            color: var(--ink);
            font-weight: 600;
        }
        .btn-success {
            background-color: var(--pop-green);
            border-color: var(--pop-green);
        }
        .btn-primary {
            background-color: var(--pop-blue);
            border-color: var(--pop-blue);
        }
        .badge-urgent {
            background-color: var(--pop-pink);
            color: #fff;
        }
        .alert-success {
            background-color: rgba(92, 184, 124, 0.15);
            border-color: var(--pop-green);
            color: #2f6b45;
        }
        .table-hover tbody tr:hover {
            background-color: rgba(74, 127, 214, 0.06);
        }
        .post-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background-color: var(--pop-blue);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.9rem;
            flex-shrink: 0;
        }
        .category-tag {
            background-color: var(--pop-blue-light);
            color: var(--pop-blue);
            font-weight: 600;
            font-size: 0.75rem;
        }
        .post-action {
            color: var(--muted);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
        }
        .post-action:hover {
            color: var(--pop-blue);
        }
        .post-card {
            border-radius: 12px;
        }
        .app-wrapper {
            display: flex;
            min-height: 100vh;
        }
        .sidebar {
            width: 220px;
            flex-shrink: 0;
            background-color: var(--pop-blue);
            padding: 24px 16px;
            position: sticky;
            top: 0;
            height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .sidebar-brand {
            color: #fff;
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            font-size: 1.4rem;
            margin-bottom: 1rem;
            display: block;
            text-decoration: none;
        }
        .sidebar-link {
            display: block;
            color: rgba(255,255,255,0.85);
            text-decoration: none;
            font-weight: 500;
            padding: 10px 12px;
            border-radius: 8px;
            margin-bottom: 4px;
        }
        .sidebar-link:hover {
            background-color: rgba(255,255,255,0.12);
            color: #fff;
        }
        .sidebar-link.active {
            background-color: #fff;
            color: var(--pop-blue);
        }
        .sidebar-footer {
            margin-top: auto;
            border-top: 1px solid rgba(255,255,255,0.2);
            padding-top: 16px;
        }
        .sidebar-user {
            color: #fff;
            font-size: 0.85rem;
            margin-bottom: 8px;
        }
        .main-content {
            flex-grow: 1;
            padding: 32px;
        }
        .auth-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px;
        }

        /* Notification bell */
        .notif-bell-wrap {
            position: relative;
            margin-bottom: 1.5rem;
        }
        .notif-bell-btn {
            background: rgba(255,255,255,0.12);
            border: none;
            color: #fff;
            width: 100%;
            text-align: left;
            padding: 10px 12px;
            border-radius: 8px;
            font-weight: 500;
            position: relative;
        }
        .notif-bell-btn:hover {
            background: rgba(255,255,255,0.2);
        }
        .notif-badge {
            background-color: var(--pop-pink);
            color: #fff;
            font-size: 0.7rem;
            font-weight: 700;
            border-radius: 999px;
            padding: 2px 7px;
            position: absolute;
            top: 6px;
            right: 10px;
        }
        .notif-dropdown {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            width: 320px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
            margin-top: 6px;
            z-index: 50;
            max-height: 360px;
            overflow-y: auto;
        }
        .notif-dropdown.show {
            display: block;
        }
        .notif-item {
            display: block;
            padding: 12px 16px;
            text-decoration: none;
            color: var(--ink);
            border-bottom: 1px solid #f0f0f0;
        }
        .notif-item:hover {
            background-color: var(--pop-blue-light);
        }
        .notif-item.unread {
            background-color: #f5f8ff;
        }
        .notif-item small {
            color: var(--muted);
        }
        .notif-empty {
            padding: 20px;
            text-align: center;
            color: var(--muted);
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
<?php if ($isAuthPage): ?>
    <div class="auth-page">
        <div style="width: 100%; max-width: 560px;">
            <a href="view_announcements.php" class="sidebar-brand d-block text-center mb-4" style="color: var(--pop-blue);">PurokInform</a>
<?php else: ?>
<div class="app-wrapper">
    <div class="sidebar">
        <a class="sidebar-brand" href="view_announcements.php">PurokInform</a>

        <?php if (isset($_SESSION['user_id'])): ?>
        <div class="notif-bell-wrap">
            <button type="button" class="notif-bell-btn" onclick="toggleNotifDropdown()">
                🔔 Notifications
                <?php if ($unreadCount > 0): ?>
                    <span class="notif-badge"><?php echo $unreadCount; ?></span>
                <?php endif; ?>
            </button>
            <div class="notif-dropdown" id="notifDropdown">
                <?php if (empty($recentNotifications)): ?>
                    <div class="notif-empty">No notifications yet.</div>
                <?php else: ?>
                    <?php foreach ($recentNotifications as $n): ?>
                        <a href="view_announcements.php" class="notif-item <?php echo !$n['is_read'] ? 'unread' : ''; ?>">
                            <div>
                                <?php if ($n['is_urgent']): ?>🔴 <?php endif; ?>
                                <?php echo htmlspecialchars($n['message'] ?: $n['title']); ?>
                            </div>
                            <small><?php echo date('M d, g:i A', strtotime($n['sent_at'])); ?></small>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <a class="sidebar-link" href="view_announcements.php">Feed</a>
        <a class="sidebar-link" href="dashboard.php">Dashboard</a>
        <a class="sidebar-link" href="search_announcements.php">Search</a>

        <div class="sidebar-footer">
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="sidebar-user">
                    <?php echo htmlspecialchars($_SESSION['first_name'] . ' (' . $_SESSION['role'] . ')'); ?>
                </div>
                <a class="sidebar-link" href="logout.php">Logout</a>
            <?php else: ?>
                <a class="sidebar-link" href="login.php">Login</a>
            <?php endif; ?>
        </div>
    </div>
    <div class="main-content">
<?php endif; ?>

<script>
function toggleNotifDropdown() {
    document.getElementById('notifDropdown').classList.toggle('show');
}
document.addEventListener('click', function(e) {
    const wrap = document.querySelector('.notif-bell-wrap');
    if (wrap && !wrap.contains(e.target)) {
        document.getElementById('notifDropdown')?.classList.remove('show');
    }
});
</script>