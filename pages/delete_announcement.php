<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/require_officer.php';
require_once __DIR__ . '/../classes/Announcement.php';

$database = new Database();
$db = $database->connect();

$id = $_GET['id'] ?? null;

if ($id) {
    $announcement = new Announcement($db);
    $record = $announcement->getAnnouncementById($id);
    if (!$record || empty($record['user_id']) || (int)$record['user_id'] !== (int)$_SESSION['user_id']) {
        http_response_code(403);
        header("Location: view_announcements.php?error=forbidden");
        exit;
    }

    try {
        $announcement->deleteWithNotifications($id, $_SESSION['user_id']);
        header("Location: view_announcements.php?deleted=1");
        exit;
    } catch (Throwable $e) {
        if ($e->getMessage() === 'You can only edit or delete your own posts.') {
            http_response_code(403);
            header("Location: view_announcements.php?error=forbidden");
            exit;
        }
        error_log('Announcement deletion transaction failed: ' . $e->getMessage());
        header("Location: view_announcements.php?error=delete_failed");
        exit;
    }
}

header("Location: view_announcements.php");
exit;