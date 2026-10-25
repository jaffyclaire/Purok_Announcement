<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/require_login.php';
require_once __DIR__ . '/../classes/Notification.php';

$database = new Database();
$db = $database->connect();
$notifModel = new Notification($db);

$userId = (int) $_SESSION['user_id'];

// Handle action: Mark single as read & navigate to target
if (isset($_GET['read_id'])) {
    $readId = (int) $_GET['read_id'];
    $notifModel->markAsRead($readId, $userId);

    $aid = isset($_GET['aid']) ? (int) $_GET['aid'] : 0;
    $eid = isset($_GET['eid']) ? (int) $_GET['eid'] : 0;

    if ($aid > 0) {
        header("Location: view_announcements.php#announcement-" . $aid);
        exit;
    } elseif ($eid > 0) {
        header("Location: events.php#event-" . $eid);
        exit;
    } else {
        header("Location: view_announcements.php");
        exit;
    }
}

// Handle action: Mark all as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_all_read'])) {
    $notifModel->markAllAsRead($userId);
    header("Location: notifications.php?marked=1");
    exit;
}

$notifications = $notifModel->getAllForUser($userId);
$unreadCount = $notifModel->getUnreadCount($userId);

if (!function_exists('timeAgo')) {
    function timeAgo($datetime) {
        $timestamp = strtotime($datetime);
        $diff = time() - $timestamp;
        if ($diff < 60) {
            return 'just now';
        } elseif ($diff < 3600) {
            $mins = floor($diff / 60);
            return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 604800) {
            $days = floor($diff / 86400);
            return $days === 1 ? 'yesterday' : $days . ' days ago';
        } elseif ($diff < 2592000) {
            $weeks = floor($diff / 604800);
            return $weeks . ' week' . ($weeks > 1 ? 's' : '') . ' ago';
        } else {
            return date('M d, Y', $timestamp);
        }
    }
}

require_once 'header.php';
?>

<div class="feed-column">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h2 mb-1">Notifications</h1>
            <p class="text-muted mb-0">Stay updated on community announcements and events.</p>
        </div>
        <?php if (!empty($notifications) && $unreadCount > 0): ?>
            <form method="POST" action="notifications.php" class="m-0">
                <button type="submit" name="mark_all_read" value="1" class="btn btn-outline-success btn-sm notif-mark-all-btn">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" aria-hidden="true"><path d="M20 6L9 17l-5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <span>Mark all as read</span>
                </button>
            </form>
        <?php endif; ?>
    </div>

    <?php if (isset($_GET['marked'])): ?>
        <div class="alert alert-success" role="status">All notifications marked as read.</div>
    <?php endif; ?>

    <?php if (empty($notifications)): ?>
        <div class="card post-card">
            <div class="empty-state text-center py-5">
                <div class="notif-empty-icon mb-3">
                    <svg viewBox="0 0 24 24" width="48" height="48" fill="none" aria-hidden="true">
                        <path d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9ZM10 21h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <h4>You're all caught up</h4>
                <p class="text-muted mb-0">You have no new notifications right now.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="card post-card overflow-hidden">
            <div class="notifications-list">
                <?php foreach ($notifications as $n): ?>
                    <?php
                    $isUnread = empty($n['is_read']);
                    $hasAnnouncement = !empty($n['announcement_id']);
                    $hasEvent = !empty($n['event_id']);
                    $title = $n['title'] ?? null;
                    $message = $n['message'] ?: ($title ?: 'Notification update');
                    $fullDate = date('F d, Y \a\t g:i A', strtotime($n['sent_at']));
                    $relTime = timeAgo($n['sent_at']);
                    $clickUrl = "notifications.php?read_id=" . (int)$n['notification_id'] .
                                ($hasAnnouncement ? "&aid=" . (int)$n['announcement_id'] : "") .
                                ($hasEvent ? "&eid=" . (int)$n['event_id'] : "");
                    ?>
                    <a href="<?php echo $clickUrl; ?>" class="notif-page-row <?php echo $isUnread ? 'is-unread' : ''; ?>">
                        <div class="notif-page-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="none">
                                <path d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9ZM10 21h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <div class="notif-page-content">
                            <div class="notif-page-message">
                                <?php echo htmlspecialchars($message); ?>
                            </div>
                            <?php if (!empty($title) && $message !== $title): ?>
                                <div class="notif-page-title text-muted small">
                                    <?php echo htmlspecialchars($title); ?>
                                </div>
                            <?php endif; ?>
                            <div class="notif-page-time" title="<?php echo htmlspecialchars($fullDate); ?>">
                                <?php echo htmlspecialchars($relTime); ?>
                            </div>
                        </div>
                        <?php if ($isUnread): ?>
                            <div class="notif-page-dot-wrap" title="Unread notification">
                                <span class="notif-page-dot" aria-label="Unread"></span>
                            </div>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'footer.php'; ?>

